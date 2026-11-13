#!/usr/bin/env python3
"""Run mandatory crawler parity via existing SSH; persist only safe evidence."""
import json
import os
from pathlib import Path
import re
import shlex
import subprocess
import sys


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
    if result.returncode != 0 or len(result.stdout) > 4096:
        raise ValueError("Host-side crawler HTTP verification did not pass.")
    evidence = json.loads(result.stdout)
    if set(evidence) != {"revision", "route_count", "noindex", "content_parity", "metadata_parity", "authenticated"}:
        raise ValueError("Unexpected crawler evidence.")
    if evidence["revision"] != revision or type(evidence["route_count"]) is not int or evidence["route_count"] != len(routes):
        raise ValueError("Crawler evidence does not match the browser routes/runtime.")
    if any(evidence[key] is not True for key in ("noindex", "content_parity", "metadata_parity", "authenticated")):
        raise ValueError("Crawler evidence is incomplete.")
    path = output / "crawler-verification.json"
    with path.open("w", opener=lambda name, flags: os.open(name, flags, 0o600)) as file:
        json.dump(evidence, file, sort_keys=True)
    print("Host-side crawler parity passed for the exact browser routes and preview revision.")


if __name__ == "__main__":
    try:
        main()
    except Exception:
        sys.exit("Crawler parity verification failed; private request details withheld.")
