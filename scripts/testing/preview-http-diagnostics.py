#!/usr/bin/env python3
"""Read-only, origin-pinned preview HTTP checks with bounded, private output."""
import base64
import json
import os
import sys
import urllib.error
import urllib.request

URL = "https://preview.tinggaljalan.com/"
AGENTS = {
    "ordinary": "TinggalJalan-ReadOnly-Preflight/1.0",
    "crawler": "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


def inspect(password, location, opener=None):
    if not isinstance(password, str) or not password:
        raise ValueError("Preview review credential is required.")
    opener = opener or urllib.request.build_opener(NoRedirect)
    token = base64.b64encode(("reviewer:" + password).encode()).decode()
    reports = []
    for kind, agent in AGENTS.items():
        request = urllib.request.Request(URL, headers={
            "Authorization": "Basic " + token, "User-Agent": agent,
        })
        try:
            response = opener.open(request, timeout=25)
        except urllib.error.HTTPError as error:
            response = error
        except Exception:
            reports.append({"location": location, "kind": kind, "status": 0,
                            "category": "transport-error", "noindex": False,
                            "basic_challenge": False})
            continue
        with response:
            body = response.read(65536).decode(errors="replace").lower()
            category = "http-response"
            if any(marker in body for marker in (
                "verify you are human", "checking your browser", "imunify360",
                "mod_security", "modsecurity", "bot verification",
            )):
                category = "security-screen"
            elif response.status == 403 and "litespeed" in body:
                category = "litespeed-forbidden"
            reports.append({"location": location, "kind": kind,
                            "status": response.status, "category": category,
                            "noindex": "noindex" in response.headers.get("X-Robots-Tag", "").lower(),
                            "basic_challenge": "basic" in response.headers.get("WWW-Authenticate", "").lower()})
    return reports


def main():
    remote = sys.argv[1:] == ["--stdin-password"]
    if sys.argv[1:] and not remote:
        raise ValueError("Unsupported diagnostics mode.")
    password = json.load(sys.stdin)["review_password"] if remote else os.environ["STAGING_REVIEW_PASSWORD"]
    for report in inspect(password, "hosting" if remote else "github"):
        print(json.dumps(report, sort_keys=True))


if __name__ == "__main__":
    try:
        main()
    except Exception:
        sys.exit("Preview HTTP diagnostics failed; sensitive request details withheld.")
