#!/usr/bin/env python3
"""Read-only, origin-pinned preview HTTP checks with bounded, private output."""
import base64
import http.cookiejar
import json
import os
import re
import sys
import urllib.error
import urllib.request
import urllib.parse

URL = "https://preview.tinggaljalan.com/"
AGENTS = {
    "ordinary": "TinggalJalan-ReadOnly-Preflight/1.0",
    "crawler": "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


def inspect(password, location, opener=None, mode="preemptive"):
    if not isinstance(password, str) or not password:
        raise ValueError("Preview review credential is required.")
    if mode not in ("preemptive", "challenge"):
        raise ValueError("Unsupported authentication mode.")
    if opener is None:
        handlers = [NoRedirect, urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())]
        if mode == "challenge":
            manager = urllib.request.HTTPPasswordMgrWithDefaultRealm()
            manager.add_password(None, URL, "reviewer", password)
            handlers.append(urllib.request.HTTPBasicAuthHandler(manager))
        opener = urllib.request.build_opener(*handlers)
    token = base64.b64encode(("reviewer:" + password).encode()).decode()
    reports = []
    for kind, agent in AGENTS.items():
        headers = {"User-Agent": agent}
        if mode == "preemptive":
            headers["Authorization"] = "Basic " + token
        request = urllib.request.Request(URL, headers=headers)
        try:
            response = opener.open(request, timeout=25)
        except urllib.error.HTTPError as error:
            response = error
        except Exception:
            reports.append({"location": location, "kind": kind, "authentication": mode, "status": 0,
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
            reports.append({"location": location, "kind": kind, "authentication": mode,
                            "status": response.status, "category": category,
                            "noindex": "noindex" in response.headers.get("X-Robots-Tag", "").lower(),
                            "basic_challenge": "basic" in response.headers.get("WWW-Authenticate", "").lower()})
    return reports


def verify_crawler(payload, opener=None):
    """Require real HTTP parity on hosting; never treat an edge 403 as a pass."""
    password, revision, routes = payload["review_password"], payload["revision"], payload["routes"]
    if not isinstance(password, str) or not password or not re.fullmatch(r"[0-9a-f]{40}", revision):
        raise ValueError("Invalid private verification configuration.")
    required = {"/", "/routes", "/news", "/about-us", "/privacy-policy"}
    if not isinstance(routes, list) or not 7 <= len(routes) <= 40 or not required.issubset(routes):
        raise ValueError("Required public review routes are incomplete.")
    for route in routes:
        if not isinstance(route, str) or len(route) > 1024 or re.search(r"[\\\x00-\x20]", route):
            raise ValueError("Invalid public route.")
        parts = urllib.parse.urlsplit(route)
        if parts.scheme or parts.netloc or parts.fragment or not route.startswith("/") or route.startswith("//"):
            raise ValueError("Public routes must remain on the preview origin.")
        decoded = urllib.parse.unquote(parts.path)
        if ".." in decoded.split("/") or not (parts.path in required or re.fullmatch(r"/(news|routes)/[a-zA-Z0-9%_-]+", parts.path)):
            raise ValueError("Only reviewed public routes are permitted.")
    if not any(r.startswith("/news/") for r in routes) or not any(r.startswith("/routes/") for r in routes):
        raise ValueError("Real public detail routes are required.")
    opener = opener or urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    token = base64.b64encode(("reviewer:" + password).encode()).decode()

    def fetch(route, agent):
        request = urllib.request.Request(URL.rstrip("/") + route, headers={
            "Authorization": "Basic " + token, "User-Agent": agent,
        })
        with opener.open(request, timeout=25) as response:
            if response.status != 200:
                raise ValueError("Public HTTP verification requires status 200.")
            data = response.read(2097153)
            if len(data) > 2097152:
                raise ValueError("Public response exceeds the verification budget.")
            if "noindex" not in response.headers.get("X-Robots-Tag", "").lower():
                raise ValueError("Preview index protection is missing.")
            return data.decode("utf-8"), response.headers.get("X-Robots-Tag", "")

    health = json.loads(fetch("/up", AGENTS["ordinary"])[0])
    if health.get("status") != "up" or health.get("revision") != revision:
        raise ValueError("Unexpected preview runtime revision.")
    fallback = re.compile(r'<main\b[^>]*class="[^"]*server-seo-content[^"]*"[^>]*>[\s\S]*?</main>')
    canonical = re.compile(r'<link\b[^>]*rel="canonical"[^>]*href="([^"]+)"')
    robots = re.compile(r'<meta\b[^>]*name="robots"[^>]*content="([^"]+)"')
    for route in routes:
        (ordinary, ordinary_robots), (crawler, crawler_robots) = (fetch(route, AGENTS[kind]) for kind in ("ordinary", "crawler"))
        if ordinary_robots != crawler_robots:
            raise ValueError("Crawler and visitor index-protection headers differ.")
        first, second = fallback.findall(ordinary), fallback.findall(crawler)
        if len(first) != 1 or first != second:
            raise ValueError("Crawler and visitor public content differ.")
        if len(canonical.findall(ordinary)) != 1 or canonical.findall(ordinary) != canonical.findall(crawler) or robots.findall(ordinary) != robots.findall(crawler):
            raise ValueError("Crawler and visitor metadata differ.")
    return {"revision": revision, "route_count": len(routes), "noindex": True,
            "content_parity": True, "metadata_parity": True, "authenticated": True}


def main():
    if sys.argv[1:] == ["--verify-crawler"]:
        print(json.dumps(verify_crawler(json.load(sys.stdin)), sort_keys=True))
        return
    remote = sys.argv[1:] == ["--stdin-password"]
    if sys.argv[1:] and not remote:
        raise ValueError("Unsupported diagnostics mode.")
    password = json.load(sys.stdin)["review_password"] if remote else os.environ["STAGING_REVIEW_PASSWORD"]
    for mode in ("preemptive", "challenge"):
        for report in inspect(password, "hosting" if remote else "github", mode=mode):
            print(json.dumps(report, sort_keys=True))


if __name__ == "__main__":
    try:
        main()
    except Exception:
        sys.exit("Preview HTTP diagnostics failed; sensitive request details withheld.")
