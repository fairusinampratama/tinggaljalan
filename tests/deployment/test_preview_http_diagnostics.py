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
        assert limit == 65536
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


if __name__ == "__main__":
    unittest.main()
