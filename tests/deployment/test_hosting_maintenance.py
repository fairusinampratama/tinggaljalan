"""Exercise real retention against disposable filesystem trees; no hosting credentials."""
import contextlib
import fcntl
import importlib.util
import io
import os
from pathlib import Path
import subprocess
import tarfile
import tempfile
import time
import types
import unittest
from unittest.mock import patch

SOURCE = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('maintenance', SOURCE / 'scripts/deployment/hosting-maintenance.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)


class HostingMaintenanceTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.account = Path(self.temp.name)
        self.account_patch = patch.object(m, 'ACCOUNT', self.account)
        self.account_patch.start()
        self.addCleanup(self.account_patch.stop)
        self.deploy = self.account / 'domains/preview.tinggaljalan.com/deployments'
        self.shared = self.deploy / 'shared'
        (self.shared / 'storage/app/public').mkdir(parents=True)
        (self.shared / '.env').write_text('secret-fixture')
        (self.shared / 'storage/app/public/logo.png').write_text('real-logo')
        (self.shared / 'backups').mkdir()
        (self.shared / 'backups/database.sql.gz').write_text('backup-fixture')
        self.releases = self.deploy / 'releases'
        self.releases.mkdir()
        self.quiet = contextlib.redirect_stdout(io.StringIO())
        self.quiet.__enter__()
        self.addCleanup(self.quiet.__exit__, None, None, None)

    def release(self, number, age=None, complete=True):
        path = self.releases / format(number, '040x')
        (path / 'public/build').mkdir(parents=True)
        (path / 'REVISION').write_text(path.name)
        if age is None:
            age = number * 10000
        timestamp = time.time() - age
        os.utime(path / 'REVISION', (timestamp, timestamp))
        (path / '.env').symlink_to(self.shared / '.env')
        (path / 'storage').symlink_to(self.shared / 'storage')
        (path / 'public/storage').symlink_to(self.shared / 'storage/app/public')
        if complete:
            (path / 'vendor').mkdir()
            (path / 'vendor/autoload.php').write_text('autoload')
            (path / 'public/build/manifest.json').write_text('{}')
        return path

    def current(self, path):
        current = self.deploy / 'current'
        if current.is_symlink():
            current.unlink()
        current.symlink_to(path)

    def test_dry_run_changes_no_release_or_shared_data(self):
        releases = [self.release(i) for i in range(1, 7)]
        self.current(releases[-1])  # active can be older than the newest rollback
        result = m.retention('preview')
        self.assertEqual(sum(r['action'] == 'remove' for r in result['releases']), 3)
        self.assertTrue(all(p.is_dir() for p in releases))
        self.assertEqual((self.shared / '.env').read_text(), 'secret-fixture')

    def test_apply_keeps_active_two_complete_rollbacks_and_shared_state(self):
        releases = [self.release(i) for i in range(1, 7)]
        self.current(releases[-1])
        m.retention('preview', apply=True)
        self.assertEqual({p.name for p in self.releases.iterdir()},
                         {releases[-1].name, releases[0].name, releases[1].name})
        self.assertEqual((self.shared / 'storage/app/public/logo.png').read_text(), 'real-logo')
        self.assertEqual((self.shared / 'backups/database.sql.gz').read_text(), 'backup-fixture')
        self.assertEqual((self.shared / '.env').read_text(), 'secret-fixture')

    def test_previous_is_pinned_even_with_older_mtime(self):
        releases = [self.release(i) for i in range(1, 7)]
        self.current(releases[0])
        m.retention('preview', apply=True, previous=releases[-1].name)
        self.assertTrue(releases[-1].exists())
        self.assertEqual(len(list(self.releases.iterdir())), 3)

    def test_incomplete_vendor_copy_does_not_count_as_rollback(self):
        releases = [self.release(i) for i in range(1, 5)]
        incomplete = self.release(6, age=5000, complete=False)
        self.current(releases[0])
        m.retention('preview', apply=True)
        self.assertFalse(incomplete.exists())
        self.assertEqual(len(list(self.releases.iterdir())), 3)

    def test_recent_release_grace(self):
        releases = [self.release(i) for i in range(1, 5)]
        recent = self.release(9, age=1, complete=False)
        self.current(releases[0])
        m.retention('preview', apply=True)
        self.assertTrue(recent.exists())

    def test_local_mutable_storage_is_never_deleted(self):
        releases = [self.release(i) for i in range(1, 5)]
        self.current(releases[0])
        local = releases[-1] / 'storage'
        local.unlink()
        local.mkdir()
        (local / 'private').write_text('private-fixture')
        m.retention('preview', apply=True)
        self.assertTrue((local / 'private').exists())

    def test_symlink_release_and_nested_symlink_never_followed(self):
        releases = [self.release(i) for i in range(1, 5)]
        self.current(releases[0])
        (self.releases / ('f' * 40)).symlink_to(self.shared)
        (releases[-1] / 'vendor/outside').symlink_to(self.shared)
        m.retention('preview', apply=True)
        self.assertTrue((self.shared / '.env').is_file())
        self.assertTrue((self.releases / ('f' * 40)).is_symlink())

    def test_wrong_revision_and_private_dump_are_preserved(self):
        releases = [self.release(i) for i in range(1, 6)]
        self.current(releases[0])
        (releases[-1] / 'REVISION').write_text('wrong')
        (releases[-2] / 'private.sql.gz').write_text('private-fixture')
        m.retention('preview', apply=True)
        self.assertTrue(releases[-1].exists())
        self.assertTrue(releases[-2].exists())

    def test_current_outside_releases_fails_closed(self):
        self.current(self.shared)
        with self.assertRaises(RuntimeError):
            m.retention('preview', apply=True)

    def test_lock_contention_refuses_cleanup(self):
        self.current(self.release(1))
        with open(self.deploy / '.lock', 'w') as lock:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            with self.assertRaises(BlockingIOError):
                m.retention('preview', apply=True)

    def test_active_change_during_cleanup_aborts(self):
        releases = [self.release(i) for i in range(1, 6)]
        self.current(releases[0])
        original = m.plan
        calls = 0
        def switch(*args):
            nonlocal calls
            calls += 1
            if calls == 2:
                self.current(releases[-1])
            return original(*args)
        with patch.object(m, 'plan', side_effect=switch):
            with self.assertRaisesRegex(RuntimeError, 'Active revision changed'):
                m.retention('preview', apply=True)
        self.assertTrue(all(p.exists() for p in releases))

    def test_capacity_reserves_future_release_and_alerts_before_budget(self):
        with patch.object(m, 'usage', return_value=(330000, 1024**3)):
            with self.assertRaises(RuntimeError):
                m.capacity(reserve=True)
        with patch.object(m, 'usage', return_value=(280000, 1024**3)):
            with self.assertRaisesRegex(RuntimeError, 'warning threshold'):
                m.capacity(alert=True)
        with patch.object(m, 'usage', return_value=(200000, 1024**3)):
            self.assertTrue(m.capacity(reserve=True)['allowed'])

    def test_byte_budget_and_incomplete_account_scan_fail_closed(self):
        with patch.object(m, 'usage', return_value=(10, m.BYTE_BUDGET)):
            with self.assertRaises(RuntimeError):
                m.capacity(reserve=True)
        with patch.object(m.subprocess, 'run', return_value=subprocess.CompletedProcess([], 1, '')):
            with self.assertRaisesRegex(RuntimeError, 'scan incomplete'):
                m.usage()

    def test_oversized_archive_fails_reservation(self):
        archive = self.account / 'release.tar.gz'
        with tarfile.open(archive, 'w:gz') as output:
            member = tarfile.TarInfo('large')
            member.size = 5000
            output.addfile(member, io.BytesIO(b'x' * 5000))
        with patch.object(m, 'RESERVE_BYTES', 100):
            with self.assertRaises(RuntimeError):
                m.archive_budget(archive)

    def test_recovery_archive_excludes_secrets_and_contains_exact_expired_plan(self):
        releases = [self.release(i) for i in range(1, 6)]
        self.current(releases[0])
        for path in releases:
            (path / 'bootstrap/cache').mkdir(parents=True)
            (path / 'bootstrap/cache/config.php').write_text('private-config-fixture')
            (path / '.env.example').write_text('template')
        output = io.BytesIO()
        with patch.object(m.sys, 'stdout', types.SimpleNamespace(buffer=output)):
            m.archive_obsolete('preview')
        output.seek(0)
        with tarfile.open(fileobj=output) as archive:
            names = archive.getnames()
            self.assertIn(releases[-1].name + '/vendor/autoload.php', names)
            self.assertFalse(any('.env' in name or '/storage' in name or '/bootstrap/cache' in name for name in names))
            self.assertNotIn(releases[0].name, names)
            manifest = __import__('json').load(archive.extractfile('MANIFEST.json'))
            self.assertEqual(manifest['digest'], m.plan_digest(m.plan('preview')))
        self.assertTrue(all(path.exists() for path in releases))

    def test_changed_archived_plan_aborts_before_deleting_anything(self):
        releases = [self.release(i) for i in range(1, 6)]
        self.current(releases[0])
        digest = m.plan_digest(m.plan('preview'))
        self.current(releases[-1])
        with self.assertRaisesRegex(RuntimeError, 'Archived retention plan changed'):
            m.retention('preview', apply=True, expected_plan=digest)
        self.assertTrue(all(path.exists() for path in releases))

    def test_real_cli_accepts_shell_inherited_locks(self):
        # Exercise the same fd 8/9 hand-off used by deploy-staging/hostinger.
        self.current(self.release(1))
        script = self.account / 'maintenance.py'
        script.write_text((SOURCE / 'scripts/deployment/hosting-maintenance.py').read_text()
                          .replace('/home/u304629909', str(self.account)))
        shell = '''set -e
exec 8>"$1/.tinggaljalan-maintenance.lock"
flock -n 8
exec 9>"$1/domains/preview.tinggaljalan.com/deployments/.lock"
flock -n 9
python3 "$1/maintenance.py" retention preview --apply --account-lock-fd 8 --lock-fd 9
'''
        result = subprocess.run(['bash', '-c', shell, 'fixture', str(self.account)], capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr.decode())


if __name__ == '__main__':
    unittest.main()
