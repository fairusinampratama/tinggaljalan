import importlib.util
import json
import os
import subprocess
import sys
import tempfile
import unittest
import urllib.error
from types import SimpleNamespace
from email.message import Message
from pathlib import Path

spec = importlib.util.spec_from_file_location("diagnostics", Path(__file__).parents[2] / "scripts/testing/preview-http-diagnostics.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
wrapper_spec = importlib.util.spec_from_file_location("crawler_wrapper", Path(__file__).parents[2] / "scripts/testing/verify-preview-crawler.py")
wrapper = importlib.util.module_from_spec(wrapper_spec)
wrapper_spec.loader.exec_module(wrapper)


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
        with self.assertRaises(module.CrawlerCheckError) as caught:
            module.verify_crawler(self.payload, self.opener("403"))
        self.assertEqual(caught.exception.evidence, {"category": "http", "route_index": 1, "kind": "crawler", "status": 403})

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

    def test_http_and_transport_errors_only_expose_bounded_facts(self):
        for error, category, status in (
            (urllib.error.HTTPError(module.URL, 401, "credential-canary secret-body", Message(), None), "http", 401),
            (RuntimeError("Authorization: credential-canary secret-body cookie"), "transport", 0),
        ):
            class Opener:
                def open(self, request, timeout):
                    raise error
            with self.assertRaises(module.CrawlerCheckError) as caught:
                module.verify_crawler(self.payload, Opener())
            value = module.safe_crawler_failure(caught.exception.evidence)
            self.assertEqual(value, {"category": category, "route_index": 0, "kind": "health", "status": status})
            for secret in ("credential-canary", "secret-body", "Authorization", "cookie", module.URL):
                self.assertNotIn(secret, json.dumps(value))

    def test_mismatch_categories_are_specific_but_content_free(self):
        for failure, category in (("content", "content"), ("metadata", "metadata"), ("header", "header"), ("noindex", "noindex"), ("revision", "runtime")):
            with self.subTest(failure=failure), self.assertRaises(module.CrawlerCheckError) as caught:
                module.verify_crawler(self.payload, self.opener(failure))
            self.assertEqual(caught.exception.evidence["category"], category)
            self.assertNotIn("Public CMS content", json.dumps(caught.exception.evidence))

    def test_probe_discovers_only_public_details_and_never_contacts_sitemap_origins(self):
        delegate = self.opener()
        class Opener:
            def open(self, request, timeout):
                if request.full_url.endswith("/sitemap.xml"):
                    return Response(200, b'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://evil.example/news/private</loc></url><url><loc>https://user:secret@preview.tinggaljalan.com/news/private</loc></url><url><loc>https://preview.tinggaljalan.com/admin/bookings</loc></url><url><loc>https://preview.tinggaljalan.com/news/%2e%2e/admin</loc></url><url><loc>https://preview.tinggaljalan.com/news/real-article</loc></url><url><loc>https://tinggaljalan.com/routes/real-package</loc></url></urlset>', {"X-Robots-Tag": "noindex"})
                return delegate.open(request, timeout)
        result = module.probe_crawler("credential-canary", Opener())
        self.assertEqual(result["route_count"], 10)
        self.assertTrue(all(request.full_url.startswith(module.URL) for request in self.requests))
        self.assertFalse(any("private" in request.full_url or "/admin" in request.full_url for request in self.requests))

    def test_probe_refuses_bad_health_and_missing_or_malformed_sitemap(self):
        for failure in ("health", "malformed", "missing", "entities"):
            delegate = self.opener("revision" if failure == "health" else None)
            class Opener:
                def open(self, request, timeout):
                    if request.full_url.endswith("/up") and failure == "health":
                        return Response(200, b'{"status":"up","revision":"invalid"}', {"X-Robots-Tag": "noindex"})
                    if request.full_url.endswith("/sitemap.xml"):
                        data = b'<!DOCTYPE urlset [<!ENTITY x "private">]><urlset/>' if failure == "entities" else b'<invalid' if failure == "malformed" else b'<urlset/>'
                        return Response(200, data, {"X-Robots-Tag": "noindex"})
                    return delegate.open(request, timeout)
            with self.subTest(failure=failure), self.assertRaises(module.CrawlerCheckError):
                module.probe_crawler("credential-canary", Opener())


class CrawlerEvidenceTests(unittest.TestCase):
    def result(self, value, code=1):
        return SimpleNamespace(returncode=code, stdout=json.dumps(value), stderr="credential-canary private-cookie body")

    def test_wrapper_relays_only_strict_failure_schema(self):
        failure = {"category": "http", "route_index": 2, "kind": "crawler", "status": 403}
        with self.assertRaises(wrapper.http.CrawlerCheckError) as caught:
            wrapper.parse_evidence(self.result({"failure": failure}))
        self.assertEqual(caught.exception.evidence, failure)
        for key, value in (("category", "credential-canary"), ("route_index", True), ("route_index", 41), ("status", True), ("status", 600), ("kind", "private-body"), ("url", "https://secret.example")):
            with self.subTest(key=key, value=value), self.assertRaises(ValueError):
                wrapper.parse_evidence(self.result({"failure": dict(failure, **{key: value})}))
        for value in ({"failure": failure, "body": "private"}, {"error": "private"}):
            with self.assertRaises(ValueError):
                wrapper.parse_evidence(self.result(value))

    def test_wrapper_keeps_success_bound_to_expected_revision_and_route_count(self):
        evidence = {"revision": "a" * 40, "route_count": 10, "noindex": True, "content_parity": True, "metadata_parity": True, "authenticated": True}
        self.assertEqual(wrapper.parse_evidence(self.result(evidence, 0), "a" * 40, 10), evidence)
        for change in ({"revision": "b" * 40}, {"route_count": 9}, {"route_count": True}, {"authenticated": False}, {"content_parity": 1}, {"private": "body"}):
            with self.subTest(change=change), self.assertRaises(ValueError):
                wrapper.parse_evidence(self.result(dict(evidence, **change), 0), "a" * 40, 10)
        with self.assertRaises(ValueError):
            wrapper.parse_evidence(SimpleNamespace(returncode=1, stdout="x" * 4097, stderr="private"))

    def test_remote_cli_failure_does_not_print_exception_or_request_secrets(self):
        script = '''
import runpy, sys, urllib.request, urllib.error
class Opener:
    def open(self, request, timeout):
        raise RuntimeError("credential-canary private-cookie response-body " + request.get_header("Authorization"))
urllib.request.build_opener = lambda *args: Opener()
sys.argv = [sys.argv[1], '--verify-crawler']
runpy.run_path(sys.argv[0], run_name='__main__')
'''
        payload = {"review_password": "credential-canary", "revision": "a" * 40,
                   "routes": ["/", "/routes", "/news", "/about-us", "/privacy-policy", "/routes/package", "/news/article"]}
        result = subprocess.run([sys.executable, "-c", script, str(Path(module.__file__))], input=json.dumps(payload), text=True, capture_output=True, timeout=10)
        self.assertEqual(result.returncode, 1)
        self.assertEqual(json.loads(result.stdout), {"failure": {"category": "transport", "route_index": 0, "kind": "health", "status": 0}})
        for private in ("credential-canary", "private-cookie", "response-body", "Authorization", "Basic "):
            self.assertNotIn(private, result.stdout + result.stderr)


class CrawlerCommandTests(unittest.TestCase):
    """Exercise the real runner entry point and report write without hosting access."""
    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.root = Path(self.directory.name)
        self.output = self.root / "review"
        self.output.mkdir(mode=0o700)
        self.config = self.root / "private.json"
        self.config.write_text(json.dumps({"review_password": "review-credential-canary", "admin_password": "admin-canary", "db_password": "database-canary"}))
        self.config.chmod(0o600)
        self.routes = ["/", "/routes", "/news", "/about-us", "/privacy-policy", "/?lang=id", "/news?lang=cn", "/routes?destination=bromo", "/routes/BROMO", "/news/preview-inline-photos", "/news/Bromo-wildfire"]
        (self.output / "crawler-routes.json").write_text(json.dumps(self.routes))
        self.evidence = {"revision": "a" * 40, "route_count": 11, "noindex": True, "content_parity": True, "metadata_parity": True, "authenticated": True}
        binary = self.root / "bin"
        binary.mkdir()
        ssh = binary / "ssh"
        ssh.write_text("#!" + sys.executable + "\n" + '''import json, os, sys
from pathlib import Path
payload = json.load(sys.stdin)
Path(os.environ['CRAWLER_TEST_CAPTURE']).write_text(json.dumps({'arguments': sys.argv[1:], 'payload': payload}))
print(os.environ['CRAWLER_TEST_RESPONSE'])
sys.exit(int(os.environ['CRAWLER_TEST_EXIT']))
''')
        ssh.chmod(0o700)
        self.environment = dict(os.environ, PATH=str(binary) + os.pathsep + os.environ.get("PATH", ""), CRAWLER_TEST_CAPTURE=str(self.root / "request.json"))

    def run_command(self, evidence=None, code=0):
        environment = dict(self.environment, CRAWLER_TEST_RESPONSE=json.dumps(self.evidence if evidence is None else evidence), CRAWLER_TEST_EXIT=str(code))
        return subprocess.run([sys.executable, str(Path(wrapper.__file__).resolve()), str(self.root / "ssh-config"), "preview", str(self.config), "a" * 40, str(self.output)],
                              text=True, capture_output=True, timeout=10, env=environment)

    def assert_private_values_absent(self, text):
        for secret in ("review-credential-canary", "admin-canary", "database-canary"):
            self.assertNotIn(secret, text)

    def test_real_command_saves_success_with_private_permissions(self):
        result = self.run_command()
        self.assertEqual(result.returncode, 0, result.stderr)
        report = self.output / "crawler-verification.json"
        self.assertEqual(json.loads(report.read_text()), self.evidence)
        self.assertEqual(report.stat().st_mode & 0o777, 0o600)
        self.assertIn("Host-side crawler parity passed", result.stdout)
        self.assert_private_values_absent(result.stdout + result.stderr + report.read_text())
        request = json.loads((self.root / "request.json").read_text())
        self.assertEqual(request["payload"], {"review_password": "review-credential-canary", "revision": "a" * 40, "routes": self.routes})
        self.assert_private_values_absent(json.dumps(request["arguments"]))

    def test_real_command_rejects_failed_remote_verification_without_report(self):
        failure = {"category": "http", "route_index": 11, "kind": "crawler", "status": 403}
        result = self.run_command({"failure": failure}, 1)
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(json.loads(result.stdout), {"failure": failure})
        self.assertFalse((self.output / "crawler-verification.json").exists())
        self.assert_private_values_absent(result.stdout + result.stderr)
        self.assertNotIn("Traceback", result.stderr)

    def test_existing_report_and_symlink_target_are_never_overwritten(self):
        report = self.output / "crawler-verification.json"
        report.write_text("previous-public-evidence")
        result = self.run_command()
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(report.read_text(), "previous-public-evidence")
        report.unlink()
        victim = self.root / "unrelated.txt"
        victim.write_text("unrelated-content")
        report.symlink_to(victim)
        result = self.run_command()
        self.assertNotEqual(result.returncode, 0)
        self.assertTrue(report.is_symlink())
        self.assertEqual(victim.read_text(), "unrelated-content")
        self.assert_private_values_absent(result.stdout + result.stderr)


if __name__ == "__main__":
    unittest.main()
