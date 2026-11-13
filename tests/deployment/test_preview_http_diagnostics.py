import importlib.util
import json
import unittest
import urllib.error
from email.message import Message
from pathlib import Path

spec = importlib.util.spec_from_file_location("diagnostics", Path(__file__).parents[2] / "scripts/testing/preview-http-diagnostics.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class Response:
    def __init__(self, status, body=b"", headers=None):
        self.status, self.body, self.headers = status, body, headers or {}

    def read(self, limit):
        assert limit in (65536, 2097153)
        return self.body[:limit]

    def __enter__(self):
        return self

    def __exit__(self, *args):
        pass


class DiagnosticsTests(unittest.TestCase):
    def test_credentials_stay_on_fixed_origin_and_out_of_evidence(self):
        requests = []
        class Opener:
            def open(self, request, timeout):
                requests.append(request)
                return Response(200, b"private-body secret-cookie", {"Set-Cookie": "secret-cookie", "X-Robots-Tag": "noindex"})
        result = module.inspect("credential-canary", "github", Opener())
        self.assertEqual(len(requests), 2)
        self.assertTrue(all(r.full_url == module.URL for r in requests))
        self.assertTrue(all(r.get_header("Authorization").startswith("Basic ") for r in requests))
        self.assertNotEqual(requests[0].get_header("User-agent"), requests[1].get_header("User-agent"))
        evidence = json.dumps(result)
        for secret in ("credential-canary", "secret-cookie", "private-body", requests[0].get_header("Authorization")):
            self.assertNotIn(secret, evidence)
        self.assertTrue(all(r["noindex"] for r in result))

    def test_redirect_cannot_forward_credentials(self):
        self.assertIsNone(module.NoRedirect().redirect_request(None, None, 302, "", Message(), "https://example.org/"))

    def test_challenge_mode_waits_for_basic_challenge(self):
        requests = []
        class Opener:
            def open(self, request, timeout):
                requests.append(request)
                return Response(401, headers={"WWW-Authenticate": "Basic realm=preview"})
        result = module.inspect("credential-canary", "github", Opener(), mode="challenge")
        self.assertTrue(all(r.get_header("Authorization") is None for r in requests))
        self.assertTrue(all(r["authentication"] == "challenge" and r["basic_challenge"] for r in result))

    def test_transport_exception_cannot_expose_headers(self):
        class Opener:
            def open(self, request, timeout):
                raise RuntimeError("Authorization: " + request.get_header("Authorization"))
        result = module.inspect("credential-canary", "hosting", Opener())
        self.assertTrue(all(r["category"] == "transport-error" and r["status"] == 0 for r in result))
        self.assertNotIn("Authorization", json.dumps(result))

    def test_forbidden_classification_retains_status_without_raw_body(self):
        class Opener:
            def open(self, request, timeout):
                return Response(403, b"LiteSpeed credential-canary", {"WWW-Authenticate": "Basic realm=private"})
        result = module.inspect("credential-canary", "github", Opener())
        self.assertTrue(all(r["category"] == "litespeed-forbidden" and r["basic_challenge"] for r in result))
        self.assertNotIn("credential-canary", json.dumps(result))


class CrawlerParityTests(unittest.TestCase):
    def setUp(self):
        self.payload = {"review_password": "credential-canary", "revision": "a" * 40,
                        "routes": ["/", "/routes", "/news", "/about-us", "/privacy-policy", "/routes/real-package", "/news/real-article"]}
        self.requests = []

    def opener(self, failure=None):
        outer = self
        class Opener:
            def open(self, request, timeout):
                outer.requests.append(request)
                bot = "Googlebot" in request.get_header("User-agent")
                headers = {"X-Robots-Tag": "noindex, nofollow"}
                if request.full_url.endswith("/up"):
                    return Response(200, json.dumps({"status": "up", "revision": ("b" if failure == "revision" else "a") * 40}).encode(), headers)
                body = '<link rel="canonical" href="https://preview.tinggaljalan.com/"><meta name="robots" content="index,follow"><main class="server-seo-content"><h1>Public CMS content</h1></main>'
                if bot and failure == "content":
                    body = body.replace("Public CMS content", "Different content")
                if bot and failure == "metadata":
                    body = body.replace("index,follow", "noindex")
                if bot and failure == "header":
                    headers = {"X-Robots-Tag": "noindex"}
                if failure == "noindex":
                    headers = {}
                return Response(403 if bot and failure == "403" else 200, body.encode(), headers)
        return Opener()

    def test_actual_http_content_metadata_runtime_and_noindex_are_required(self):
        evidence = module.verify_crawler(self.payload, self.opener())
        self.assertEqual(evidence["route_count"], 7)
        self.assertTrue(evidence["content_parity"] and evidence["metadata_parity"] and evidence["noindex"])
        self.assertEqual(len(self.requests), 15)
        self.assertTrue(all(r.full_url.startswith(module.URL) for r in self.requests))
        self.assertTrue(all(r.get_header("Authorization").startswith("Basic ") for r in self.requests))
        self.assertNotIn("credential-canary", json.dumps(evidence))
        self.assertNotIn("Public CMS content", json.dumps(evidence))

    def test_crawler_403_is_never_accepted(self):
        with self.assertRaises(ValueError):
            module.verify_crawler(self.payload, self.opener("403"))

    def test_content_metadata_index_protection_or_runtime_mismatch_fail(self):
        for failure in ("content", "metadata", "header", "noindex", "revision"):
            with self.subTest(failure=failure), self.assertRaises(ValueError):
                module.verify_crawler(self.payload, self.opener(failure))

    def test_external_private_and_traversal_routes_fail_before_network(self):
        for route in ("https://example.org/", "//example.org/", "/admin/bookings", "/news/%2e%2e/admin", "/news/test\n", "/news/a#fragment"):
            with self.subTest(route=route), self.assertRaises(ValueError):
                module.verify_crawler(dict(self.payload, routes=self.payload["routes"] + [route]), self.opener())
        self.assertEqual(self.requests, [])

    def test_missing_real_public_detail_or_core_route_fails(self):
        for routes in ([], self.payload["routes"][:5], self.payload["routes"][1:] + ["/news/extra"]):
            with self.assertRaises(ValueError):
                module.verify_crawler(dict(self.payload, routes=routes), self.opener())


if __name__ == "__main__":
    unittest.main()
