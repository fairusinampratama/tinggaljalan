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
import xml.etree.ElementTree as ET

URL = "https://preview.tinggaljalan.com/"
AGENTS = {
    "ordinary": "TinggalJalan-ReadOnly-Preflight/1.0",
    "crawler": "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
}

FAILURE_CATEGORIES = {
    "configuration", "routes", "http", "transport", "response-size", "noindex",
    "runtime", "content", "metadata", "header", "sitemap",
}


class CrawlerCheckError(ValueError):
    """Only bounded, non-sensitive facts may cross the SSH diagnostics boundary."""
    def __init__(self, category, route_index=0, kind="input", status=0):
        self.evidence = {"category": category, "route_index": route_index, "kind": kind, "status": status}
        super().__init__("Crawler verification did not pass.")


def safe_crawler_failure(value):
    if not isinstance(value, dict) or set(value) != {"category", "route_index", "kind", "status"}:
        raise ValueError("Invalid diagnostic evidence.")
    if value["category"] not in FAILURE_CATEGORIES or value["kind"] not in ("input", "health", "sitemap", "ordinary", "crawler", "pair"):
        raise ValueError("Invalid diagnostic category.")
    if type(value["route_index"]) is not int or not 0 <= value["route_index"] <= 40:
        raise ValueError("Invalid diagnostic route index.")
    if type(value["status"]) is not int or not (value["status"] == 0 or 100 <= value["status"] <= 599):
        raise ValueError("Invalid diagnostic HTTP status.")
    return dict(value)


def public_opener():
    return urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def fetch_public(opener, password, route, kind, route_index=0):
    token = base64.b64encode(("reviewer:" + password).encode()).decode()
    request = urllib.request.Request(URL.rstrip("/") + route, headers={
        "Authorization": "Basic " + token, "User-Agent": AGENTS.get(kind, AGENTS["ordinary"]),
    })
    try:
        with opener.open(request, timeout=25) as response:
            if response.status != 200:
                raise CrawlerCheckError("http", route_index, kind, response.status)
            data = response.read(2097153)
            if len(data) > 2097152:
                raise CrawlerCheckError("response-size", route_index, kind, 200)
            if "noindex" not in response.headers.get("X-Robots-Tag", "").lower():
                raise CrawlerCheckError("noindex", route_index, kind, 200)
            return data.decode("utf-8"), response.headers.get("X-Robots-Tag", "")
    except CrawlerCheckError:
        raise
    except urllib.error.HTTPError as error:
        status = error.code
        error.close()
        raise CrawlerCheckError("http", route_index, kind, status) from None
    except Exception:
        raise CrawlerCheckError("transport", route_index, kind) from None


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
        raise CrawlerCheckError("configuration")
    required = {"/", "/routes", "/news", "/about-us", "/privacy-policy"}
    if not isinstance(routes, list) or not 7 <= len(routes) <= 40 or not required.issubset(routes):
        raise CrawlerCheckError("routes")
    for index, route in enumerate(routes, 1):
        if not isinstance(route, str) or len(route) > 1024 or re.search(r"[\\\x00-\x20]", route):
            raise CrawlerCheckError("routes", index)
        parts = urllib.parse.urlsplit(route)
        if parts.scheme or parts.netloc or parts.fragment or not route.startswith("/") or route.startswith("//"):
            raise CrawlerCheckError("routes", index)
        decoded = urllib.parse.unquote(parts.path)
        if ".." in decoded.split("/") or not (parts.path in required or re.fullmatch(r"/(news|routes)/[a-zA-Z0-9%_-]+", parts.path)):
            raise CrawlerCheckError("routes", index)
    if not any(r.startswith("/news/") for r in routes) or not any(r.startswith("/routes/") for r in routes):
        raise CrawlerCheckError("routes")
    opener = opener or public_opener()
    try:
        health = json.loads(fetch_public(opener, password, "/up", "health")[0])
    except (json.JSONDecodeError, TypeError):
        raise CrawlerCheckError("runtime", kind="health") from None
    if health.get("status") != "up" or health.get("revision") != revision:
        raise CrawlerCheckError("runtime", kind="health")
    fallback = re.compile(r'<main\b[^>]*class="[^"]*server-seo-content[^"]*"[^>]*>[\s\S]*?</main>')
    canonical = re.compile(r'<link\b[^>]*rel="canonical"[^>]*href="([^"]+)"')
    robots = re.compile(r'<meta\b[^>]*name="robots"[^>]*content="([^"]+)"')
    for index, route in enumerate(routes, 1):
        (ordinary, ordinary_robots), (crawler, crawler_robots) = (fetch_public(opener, password, route, kind, index) for kind in ("ordinary", "crawler"))
        if ordinary_robots != crawler_robots:
            raise CrawlerCheckError("header", index, "pair", 200)
        first, second = fallback.findall(ordinary), fallback.findall(crawler)
        if len(first) != 1 or first != second:
            raise CrawlerCheckError("content", index, "pair", 200)
        if len(canonical.findall(ordinary)) != 1 or canonical.findall(ordinary) != canonical.findall(crawler) or robots.findall(ordinary) != robots.findall(crawler):
            raise CrawlerCheckError("metadata", index, "pair", 200)
    return {"revision": revision, "route_count": len(routes), "noindex": True,
            "content_parity": True, "metadata_parity": True, "authenticated": True}


def probe_crawler(password, opener=None):
    """Read-only diagnosis of current preview; never creates promotion evidence."""
    if not isinstance(password, str) or not password:
        raise CrawlerCheckError("configuration")
    opener = opener or public_opener()
    try:
        health = json.loads(fetch_public(opener, password, "/up", "health")[0])
        revision = health["revision"]
        if health["status"] != "up" or not isinstance(revision, str) or not re.fullmatch(r"[0-9a-f]{40}", revision):
            raise ValueError()
    except (ValueError, KeyError, TypeError) as error:
        if isinstance(error, CrawlerCheckError):
            raise
        raise CrawlerCheckError("runtime", kind="health") from None
    raw = fetch_public(opener, password, "/sitemap.xml", "sitemap")[0]
    try:
        if re.search(r"<!\s*(?:DOCTYPE|ENTITY)\b", raw, re.I):
            raise ET.ParseError()
        document = ET.fromstring(raw)
    except ET.ParseError:
        raise CrawlerCheckError("sitemap", kind="sitemap") from None
    details = {}
    for element in document.iter():
        if element.tag.rsplit("}", 1)[-1] != "loc":
            continue
        try:
            parts = urllib.parse.urlsplit(element.text or "")
            if parts.scheme != "https" or parts.netloc not in ("preview.tinggaljalan.com", "tinggaljalan.com") or parts.query or parts.fragment:
                continue
            match = re.fullmatch(r"/(news|routes)/[a-zA-Z0-9%_-]+", parts.path)
            if match and ".." not in urllib.parse.unquote(parts.path).split("/"):
                details.setdefault(match[1], parts.path)
        except ValueError:
            continue
    if set(details) != {"news", "routes"}:
        raise CrawlerCheckError("sitemap", kind="sitemap")
    routes = ["/", "/routes", "/news", "/about-us", "/privacy-policy", "/?lang=id", "/news?lang=cn", "/routes?destination=bromo", details["routes"], details["news"]]
    return verify_crawler({"review_password": password, "revision": revision, "routes": routes}, opener)


def main():
    if sys.argv[1:] == ["--verify-crawler"]:
        print(json.dumps(verify_crawler(json.load(sys.stdin)), sort_keys=True))
        return
    if sys.argv[1:] == ["--probe-crawler"]:
        print(json.dumps(probe_crawler(json.load(sys.stdin)["review_password"]), sort_keys=True))
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
    except CrawlerCheckError as error:
        print(json.dumps({"failure": safe_crawler_failure(error.evidence)}, sort_keys=True))
        sys.exit(1)
    except Exception:
        sys.exit("Preview HTTP diagnostics failed; sensitive request details withheld.")
