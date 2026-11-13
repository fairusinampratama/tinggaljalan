#!/usr/bin/env python3
"""Run mandatory crawler parity via existing SSH; persist only safe evidence."""
import json
import importlib.util
import os
from pathlib import Path
import re
import shlex
import subprocess
import sys

spec = importlib.util.spec_from_file_location("preview_http", Path(__file__).with_name("preview-http-diagnostics.py"))
http = importlib.util.module_from_spec(spec)
spec.loader.exec_module(http)


def parse_evidence(result, revision=None, route_count=None):
    if len(result.stdout) > 4096:
        raise ValueError("Unexpected crawler evidence size.")
    evidence = json.loads(result.stdout)
    if result.returncode != 0:
        if isinstance(evidence, dict) and set(evidence) == {"failure"}:
            failure = http.safe_crawler_failure(evidence["failure"])
            raise http.CrawlerCheckError(**failure)
        raise ValueError("Host-side crawler HTTP verification did not pass.")
    if set(evidence) != {"revision", "route_count", "noindex", "content_parity", "metadata_parity", "authenticated"}:
        raise ValueError("Unexpected crawler evidence.")
    if not isinstance(evidence["revision"], str) or not re.fullmatch(r"[0-9a-f]{40}", evidence["revision"]):
        raise ValueError("Invalid crawler runtime evidence.")
    if type(evidence["route_count"]) is not int or not 7 <= evidence["route_count"] <= 40:
        raise ValueError("Invalid crawler route count.")
    if (revision is not None and evidence["revision"] != revision) or (route_count is not None and evidence["route_count"] != route_count):
        raise ValueError("Crawler evidence does not match the browser routes/runtime.")
    if any(evidence[key] is not True for key in ("noindex", "content_parity", "metadata_parity", "authenticated")):
        raise ValueError("Crawler evidence is incomplete.")
    return evidence


def main():
    ssh_config, alias, private_config, revision, output = sys.argv[1:]
    if alias not in ("preview", "staging") or not re.fullmatch(r"[0-9a-f]{40}", revision):
        raise ValueError("Invalid verification target.")
    output = Path(output)
    config = json.loads(Path(private_config).read_text())
    routes = json.loads((output / "crawler-routes.json").read_text())
    source = Path(__file__).with_name("preview-http-diagnostics.py").read_text()
    command = "python3 -c " + shlex.quote(source) + " --verify-crawler"
    payload = {"review_password": config["review_password"], "revision": revision, "routes": routes}
    result = subprocess.run(["ssh", "-F", ssh_config, alias, command], input=json.dumps(payload),
                            capture_output=True, text=True, timeout=300)
    evidence = parse_evidence(result, revision, len(routes))
    path = output / "crawler-verification.json"
    with open(path, "x", opener=lambda name, flags: os.open(name, flags | os.O_NOFOLLOW, 0o600)) as file:
        json.dump(evidence, file, sort_keys=True)
    print("Host-side crawler parity passed for the exact browser routes and preview revision.")


if __name__ == "__main__":
    try:
        main()
    except http.CrawlerCheckError as error:
        print(json.dumps({"failure": http.safe_crawler_failure(error.evidence)}, sort_keys=True))
        sys.exit("Crawler parity verification failed; only bounded diagnostic facts were logged.")
    except subprocess.TimeoutExpired:
        sys.exit("Crawler parity verification failed: SSH verification timed out; private details withheld.")
    except Exception:
        sys.exit("Crawler parity verification failed; private request details withheld.")
