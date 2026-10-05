"""Exercise staging publication/rollback using local fake PHP and HTTP adapters."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile
import unittest
import hashlib

SOURCE = Path(__file__).resolve().parents[2]
SHA = "a" * 40


class StagingTransactionTest(unittest.TestCase):
    def test_production_root_is_rejected_before_any_work(self):
        result = subprocess.run(["bash", str(SOURCE / "scripts/deployment/deploy-staging.sh"),
                                 "/home/u304629909/domains/tinggaljalan.com", SHA, "unused", "0" * 64, "unused"], capture_output=True)
        self.assertNotEqual(result.returncode, 0)

    def run_deployment(self, previous, fail):
        with tempfile.TemporaryDirectory() as temporary:
            work = Path(temporary)
            root = work / "preview.tinggaljalan.com"
            public = root / "public_html"
            shared = root / "deployments/shared"
            incoming = root / "deployments/incoming"
            releases = root / "deployments/releases"
            for directory in [public, shared, incoming, releases]:
                directory.mkdir(parents=True, exist_ok=True)
            (root / ".tinggaljalan-staging").write_text("preview.tinggaljalan.com\n")
            (public / "index.php").write_text("previous-wrapper")
            if previous:
                old = releases / ("b" * 40)
                old.mkdir()
                (old / 'REVISION').write_text('b' * 40)
                (public / ('index-' + 'b' * 40 + '.php')).write_text('previous-wrapper')
                (root / "deployments/current").symlink_to(old)
            package = work / "package"
            for directory in ["vendor", "public/build", "public/images", "scripts/deployment", "bootstrap/cache"]:
                (package / directory).mkdir(parents=True, exist_ok=True)
            for filename in ["vendor/autoload.php", "artisan", "public/index.php", "scripts/deployment/configure-staging.php"]:
                (package / filename).write_text("")
            for image in ["hero-bromo.jpg", "destination-tumpak-sewu.jpg"]:
                (package / "public/images" / image).write_text("image")
            (package / "public/build/manifest.json").write_text(json.dumps({"resources/js/app.jsx": {"file": "assets/app.js", "css": ["assets/app.css"]}}))
            (package / "REVISION").write_text(SHA + "\n")
            archive = incoming / f"release-{SHA}.tar.gz"
            with tarfile.open(archive, "w:gz") as output:
                output.add(package, arcname=".")
            digest = hashlib.sha256(archive.read_bytes()).hexdigest()
            config = incoming / f"config-{SHA}.json"
            config.write_text(json.dumps({"review_password": "local-fixture-password"}))
            adapters = work / "bin"
            adapters.mkdir()
            php = adapters / "php-adapter"
            php.write_text("#!/usr/bin/env bash\nset -e\n"
                           f"if [[ $1 == -r ]]; then exec '{shutil.which('php')}' \"$@\"; fi\n"
                           'if [[ $1 == *configure-staging.php && $4 == configure ]]; then\n'
                           '  printf env > "$3/deployments/shared/.env"\n'
                           '  printf auth > "$3/deployments/shared/.htpasswd"\n'
                           'fi\n')
            php.chmod(0o755)
            curl = adapters / "curl"
            curl.write_text("#!/usr/bin/env python3\nimport sys,os\n"
                            "args=sys.argv[1:]; url=args[-1]; authenticated='--netrc-file' in args\n"
                            "if '--write-out' in args:\n"
                            " print(('403' if url.endswith('/.env') or url.endswith('/.htpasswd') else '200') if authenticated else '401',end=''); sys.exit()\n"
                            "if not authenticated: sys.exit(1)\n"
                            "if '-o' in args and args[args.index('-o')+1] != '/dev/null':\n"
                            " open(args[args.index('-o')+1],'w').write('<script src=\"/build/assets/app.js\"></script><link href=\"/build/assets/app.css\">')\n"
                            "if url.endswith('/news') and os.environ.get('FAIL_HTTP')=='1': sys.exit(22)\n"
                            "if '-D' in args: print('X-Robots-Tag: noindex, nofollow'); sys.exit()\n"
                            f"if '/up?' in url: print('{{\"status\":\"up\",\"revision\":\"{SHA}\"}}')\n")
            curl.chmod(0o755)
            script = work / "deploy.sh"
            script.write_text((SOURCE / "scripts/deployment/deploy-staging.sh").read_text()
                              .replace("/home/u304629909/domains/preview.tinggaljalan.com", str(root))
                              .replace("/opt/alt/php84/usr/bin/php", str(php)))
            environment = {**os.environ, "PATH": str(adapters) + ":" + os.environ["PATH"], "FAIL_HTTP": str(int(fail))}
            result = subprocess.run(["bash", str(script), str(root), SHA, str(archive), digest, str(config)],
                                    env=environment, capture_output=True, text=True)
            self.assertEqual(result.returncode == 0, not fail, result.stderr)
            self.assertFalse(config.exists(), "Transferred secret must be removed")
            self.assertIn("Require valid-user", (public / ".htaccess").read_text())
            if fail and previous:
                self.assertEqual((root / "deployments/current").resolve(), old)
                self.assertEqual((public / "index.php").read_text(), "previous-wrapper")
                self.assertIn('DirectoryIndex index-' + 'b' * 40, (public / '.htaccess').read_text())
                self.assertFalse((releases / SHA).exists())
            elif fail:
                self.assertFalse((root / "deployments/current").exists())
                self.assertIn("503", (public / "index.php").read_text())
                self.assertFalse((releases / SHA).exists())
            else:
                self.assertEqual((root / "deployments/current").resolve(), releases / SHA)
                self.assertIn(SHA, (public / f"index-{SHA}.php").read_text())
                self.assertIn(f'DirectoryIndex index-{SHA}.php', (public / '.htaccess').read_text())

    def test_success_publishes_selected_revision(self):
        self.run_deployment(previous=True, fail=False)

    def test_failed_http_restores_previous_release(self):
        self.run_deployment(previous=True, fail=True)

    def test_first_deployment_failure_remains_protected(self):
        self.run_deployment(previous=False, fail=True)


if __name__ == "__main__":
    unittest.main()
