"""Run the production shell transaction against disposable filesystem adapters."""
import os
from pathlib import Path
import subprocess
import tarfile
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
NEW = 'a' * 40
OLD = 'b' * 40


class RecoveryTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.work = Path(self.temporary.name)
        self.domain = self.work / 'domains/tinggaljalan.com'
        self.deployments = self.domain / 'deployments'
        self.releases = self.deployments / 'releases'
        self.shared = self.deployments / 'shared'
        self.incoming = self.deployments / 'incoming'
        self.bin = self.work / 'bin'
        for path in (self.domain / 'public_html', self.shared / 'storage/app/public',
                     self.incoming, self.releases, self.bin):
            path.mkdir(parents=True)
        (self.shared / '.env').write_text('preserved-production-configuration')
        (self.shared / 'storage/app/public/logo.png').write_bytes(b'original-logo')
        self.old = self.releases / OLD
        self.old.mkdir()
        (self.old / 'vendor').mkdir()
        (self.old / 'vendor/autoload.php').touch()
        (self.old / 'artisan').touch()
        (self.old / 'REVISION').write_text(OLD)
        (self.deployments / 'current').symlink_to(self.old)
        (self.domain / 'tinggaljalan-app').symlink_to(self.deployments / 'current')
        self.calls = self.work / 'calls'
        self.maintenance = self.shared / 'storage/maintenance'
        self.adapter('php', '''#!/usr/bin/env bash
set -e
printf '%s\\n' "$*" >> "$CALLS"
command="${2:-}"
if [[ "$command" == down ]]; then touch "$MAINTENANCE"; fi
if [[ "$command" == up ]]; then rm -f "$MAINTENANCE"; fi
if [[ "$command" == "${FAIL_AT:-unreachable}" && "$1" != */''' + OLD + '''/artisan ]]; then exit 1; fi
if [[ "$command" == down && "${FAIL_AT:-}" == down ]]; then exit 1; fi
if [[ "$command" == "${SIGNAL_AT:-unreachable}" ]]; then kill -"$SIGNAL" "$PPID"; fi
''')
        for name in ('composer', 'mysqldump', 'pgrep'):
            self.adapter(name, '#!/usr/bin/env bash\nexit 0\n')
        self.package = self.work / 'package'
        for name in ('vendor', 'public/build', 'public/images', 'bootstrap/cache', 'scripts/deployment'):
            (self.package / name).mkdir(parents=True)
        (self.package / 'vendor/autoload.php').touch()
        (self.package / 'artisan').touch()
        (self.package / 'REVISION').write_text(NEW)
        (self.package / 'scripts/deployment/hosting-maintenance.py').write_text(
            "import os,sys\nif 'retention' in sys.argv and os.environ.get('FAIL_RETENTION'): sys.exit(1)\n")
        health = self.package / 'scripts/deployment/health-check.sh'
        health.write_text('''#!/usr/bin/env bash
if [[ "${SIGNAL_AT:-}" == health ]]; then kill -"$SIGNAL" "$PPID"; fi
[[ "${FAIL_AT:-}" != health ]]
''')
        health.chmod(0o755)
        self.archive = self.incoming / ('release-' + NEW + '.tar.gz')
        self.upload()
        script = (ROOT / 'scripts/deployment/deploy-hostinger.sh').read_text()
        script = script.replace('/opt/alt/php84/usr/bin/php', str(self.bin / 'php'))
        script = script.replace('/home/u304629909', str(self.work))
        self.script = self.work / 'deploy.sh'
        self.script.write_text(script)

    def adapter(self, name, content):
        path = self.bin / name
        path.write_text(content)
        path.chmod(0o755)

    def upload(self):
        with tarfile.open(self.archive, 'w:gz') as archive:
            archive.add(self.package, arcname='.')

    def run_deploy(self, **overrides):
        env = dict(os.environ, PATH=str(self.bin) + ':' + os.environ['PATH'],
                   CALLS=str(self.calls), MAINTENANCE=str(self.maintenance), **overrides)
        return subprocess.run(['bash', str(self.script), str(self.domain), NEW,
                               str(self.archive), 'https://tinggaljalan.com'],
                              env=env, capture_output=True, text=True, timeout=20)

    def assert_preserved(self):
        self.assertEqual((self.shared / '.env').read_text(), 'preserved-production-configuration')
        self.assertEqual((self.shared / 'storage/app/public/logo.png').read_bytes(), b'original-logo')
        self.assertFalse(self.maintenance.exists())

    def test_failures_restore_previous_and_allow_same_sha_retry(self):
        for point in ('about', 'config:cache', 'deploy:backup-database', 'down', 'migrate', 'health'):
            with self.subTest(point=point):
                self.upload()
                result = self.run_deploy(FAIL_AT=point)
                self.assertNotEqual(result.returncode, 0, result.stdout)
                self.assertEqual((self.deployments / 'current').resolve(), self.old, result.stderr)
                self.assertFalse((self.releases / NEW).exists(), result.stderr)
                self.assertFalse((self.deployments / '.production-deployment-state').exists())
                self.assert_preserved()
        self.upload()
        result = self.run_deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual((self.deployments / 'current').resolve(), self.releases / NEW)
        self.assert_preserved()

    def test_term_before_and_after_switch_recovers(self):
        for point in ('down', 'health'):
            with self.subTest(point=point):
                self.upload()
                result = self.run_deploy(SIGNAL_AT=point, SIGNAL='TERM')
                self.assertNotEqual(result.returncode, 0)
                self.assertEqual((self.deployments / 'current').resolve(), self.old)
                self.assertFalse((self.releases / NEW).exists())
                self.assert_preserved()

    def test_sigkill_is_recovered_by_next_invocation(self):
        result = self.run_deploy(SIGNAL_AT='health', SIGNAL='KILL')
        self.assertNotEqual(result.returncode, 0)
        journal = self.deployments / '.production-deployment-state'
        self.assertTrue(journal.exists())
        self.assertEqual(journal.stat().st_mode & 0o777, 0o600)
        self.assertEqual((self.deployments / 'current').resolve(), self.releases / NEW)
        result = self.run_deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('Recovered interrupted deployment', result.stdout)
        self.assertFalse(journal.exists())
        self.assert_preserved()

    def test_existing_unowned_candidate_is_never_deleted(self):
        candidate = self.releases / NEW
        candidate.mkdir()
        marker = candidate / 'unique-work'
        marker.write_text('keep')
        result = self.run_deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(marker.read_text(), 'keep')
        self.assertEqual((self.deployments / 'current').resolve(), self.old)

    def test_invalid_pending_state_fails_closed(self):
        journal = self.deployments / '.production-deployment-state'
        journal.write_text('../outside\n' + OLD + '\nmaintenance\n')
        result = self.run_deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertTrue(journal.exists())
        self.assertEqual((self.deployments / 'current').resolve(), self.old)
        self.assert_preserved()

    def test_recovery_refuses_to_overwrite_an_unrelated_active_release(self):
        self.run_deploy(SIGNAL_AT='health', SIGNAL='KILL')
        other = self.releases / ('c' * 40)
        other.mkdir()
        current = self.deployments / 'current'
        current.unlink()
        current.symlink_to(other)
        result = self.run_deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(current.resolve(), other)
        self.assertTrue((self.releases / NEW).exists())
        self.assertTrue((self.deployments / '.production-deployment-state').exists())

    def test_pending_next_link_is_cleaned_only_when_journal_owned(self):
        self.run_deploy(SIGNAL_AT='down', SIGNAL='KILL')
        (self.deployments / 'current.next').symlink_to(self.releases / NEW)
        result = self.run_deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assert_preserved()

    def test_rollback_failure_after_down_brings_previous_release_online(self):
        import shutil
        target = self.releases / NEW
        shutil.copytree(self.package, target)
        script = (ROOT / 'scripts/deployment/rollback-hostinger.sh').read_text()
        script = script.replace('/opt/alt/php84/usr/bin/php', str(self.bin / 'php'))
        script = script.replace('/home/u304629909', str(self.work))
        rollback = self.work / 'rollback.sh'
        rollback.write_text(script)
        env = dict(os.environ, PATH=str(self.bin) + ':' + os.environ['PATH'],
                   CALLS=str(self.calls), MAINTENANCE=str(self.maintenance), FAIL_AT='down')
        result = subprocess.run(['bash', str(rollback), str(self.domain), NEW,
                                 'https://tinggaljalan.com'], env=env, capture_output=True, text=True)
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual((self.deployments / 'current').resolve(), self.old)
        self.assert_preserved()

    def test_sigkill_during_manual_rollback_is_recovered_and_retried(self):
        import shutil
        target = self.releases / NEW
        shutil.copytree(self.package, target)
        script = (ROOT / 'scripts/deployment/rollback-hostinger.sh').read_text()
        script = script.replace('/opt/alt/php84/usr/bin/php', str(self.bin / 'php'))
        script = script.replace('/home/u304629909', str(self.work))
        rollback = self.work / 'rollback.sh'
        rollback.write_text(script)
        env = dict(os.environ, PATH=str(self.bin) + ':' + os.environ['PATH'],
                   CALLS=str(self.calls), MAINTENANCE=str(self.maintenance))
        command = ['bash', str(rollback), str(self.domain), NEW, 'https://tinggaljalan.com']
        result = subprocess.run(command, env=dict(env, SIGNAL_AT='health', SIGNAL='KILL'),
                                capture_output=True, text=True)
        self.assertNotEqual(result.returncode, 0)
        journal = self.deployments / '.production-deployment-state'
        self.assertTrue(journal.exists())
        self.assertIn('rollback', journal.read_text())
        result = subprocess.run(command, env=env, capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('Recovered interrupted deployment', result.stdout)
        self.assertEqual((self.deployments / 'current').resolve(), target)
        self.assertTrue(self.old.exists(), 'Rollback must retain the previous release')
        self.assertFalse(journal.exists())
        self.assert_preserved()

    def test_deploy_recovery_never_deletes_an_existing_manual_rollback_target(self):
        import shutil
        target = self.releases / NEW
        shutil.copytree(self.package, target)
        journal = self.deployments / '.production-deployment-state'
        journal.write_text(NEW + '\n' + OLD + '\nrollback\n')
        current = self.deployments / 'current'
        current.unlink()
        current.symlink_to(target)
        result = self.run_deploy()
        self.assertNotEqual(result.returncode, 0, 'Existing rollback code is not a disposable deploy candidate')
        self.assertEqual(current.resolve(), self.old)
        self.assertTrue(target.exists())
        self.assertFalse(journal.exists())
        self.assert_preserved()

    def test_housekeeping_failure_keeps_healthy_release(self):
        result = self.run_deploy(FAIL_RETENTION='1')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('post-success retention needs attention', result.stderr)
        self.assertEqual((self.deployments / 'current').resolve(), self.releases / NEW)
        self.assertFalse((self.deployments / '.production-deployment-state').exists())
        self.assert_preserved()


if __name__ == '__main__':
    unittest.main()
