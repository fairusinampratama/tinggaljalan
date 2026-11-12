import importlib.util
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('preview_state', ROOT / 'scripts/deployment/preview-release-state.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)
OLD = 'b' * 40
NEW = 'a' * 40
DIGEST = 'd' * 64


class PreviewStateTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.account = Path(self.temporary.name)
        self.root = self.account / 'domains/preview.tinggaljalan.com'
        self.deployment = self.root / 'deployments'
        self.shared = self.deployment / 'shared'
        self.public = self.root / 'public_html'
        self.public.mkdir(parents=True)
        (self.shared / 'storage/app/public').mkdir(parents=True)
        (self.shared / '.env').write_text('preserve-config')
        (self.shared / 'storage/app/public/logo.png').write_bytes(b'preserve-logo')
        (self.root / '.tinggaljalan-staging').write_text('preview.tinggaljalan.com')
        self.old = self.release(OLD)
        (self.public / ('index-' + OLD + '.php')).write_text('previous-wrapper')
        self.rules = 'Require valid-user\nDirectoryIndex index-' + OLD + '.php\n'
        (self.public / '.htaccess').write_text(self.rules)
        (self.deployment / 'current').symlink_to(self.old)
        for key, value in [('ACCOUNT', self.account), ('PHP', '/bin/true')]:
            setting = patch.object(m, key, value)
            setting.start()
            self.addCleanup(setting.stop)

    def release(self, sha):
        release = self.deployment / 'releases' / sha
        (release / 'vendor').mkdir(parents=True)
        (release / 'vendor/autoload.php').touch()
        (release / 'artisan').touch()
        (release / 'REVISION').write_text(sha)
        (release / '.env').symlink_to(self.shared / '.env')
        (release / 'storage').symlink_to(self.shared / 'storage')
        return release

    def begin(self):
        m.transaction('begin', NEW, DIGEST, '123-1')
        journal = self.deployment / '.preview-release-pending.json'
        self.assertEqual(journal.stat().st_mode & 0o777, 0o600)

    def switch(self):
        candidate = self.release(NEW)
        (candidate / '.release-archive.sha256').write_text(DIGEST)
        current = self.deployment / 'current'
        current.unlink()
        current.symlink_to(candidate)
        (self.public / '.htaccess').write_text('candidate rules')

    def preserved(self):
        self.assertEqual((self.shared / '.env').read_text(), 'preserve-config')
        self.assertEqual((self.shared / 'storage/app/public/logo.png').read_bytes(), b'preserve-logo')

    def test_browser_failure_restores_code_and_access_protection(self):
        self.begin()
        self.switch()
        m.transaction('rollback', NEW, DIGEST, '123-1')
        self.assertEqual((self.deployment / 'current').resolve(), self.old)
        self.assertEqual((self.public / '.htaccess').read_text(), self.rules)
        self.assertFalse((self.deployment / 'releases' / NEW).exists())
        self.assertFalse((self.deployment / '.preview-release-pending.json').exists())
        self.preserved()

    def test_failure_before_publication_preserves_previous_code(self):
        self.begin()
        m.transaction('rollback', NEW, DIGEST, '123-1')
        self.assertEqual((self.deployment / 'current').resolve(), self.old)
        self.preserved()

    def test_finalize_requires_exact_hosted_archive_and_preserves_new_code(self):
        self.begin()
        self.switch()
        m.transaction('finalize', NEW, DIGEST, '123-1')
        self.assertEqual((self.deployment / 'current').resolve().name, NEW)
        self.assertFalse((self.deployment / '.preview-release-pending.json').exists())
        self.preserved()

    def test_wrong_owner_or_digest_cannot_finalize_or_rollback(self):
        self.begin()
        self.switch()
        for operation in ('finalize', 'rollback'):
            with self.assertRaises(RuntimeError):
                m.transaction(operation, NEW, DIGEST, '124-1')
        (self.deployment / 'releases' / NEW / '.release-archive.sha256').write_text('changed')
        with self.assertRaises(RuntimeError):
            m.transaction('finalize', NEW, DIGEST, '123-1')
        self.assertTrue((self.deployment / '.preview-release-pending.json').exists())

    def test_unrelated_active_release_is_never_overwritten(self):
        self.begin()
        foreign = self.release('c' * 40)
        current = self.deployment / 'current'
        current.unlink()
        current.symlink_to(foreign)
        with self.assertRaises(RuntimeError):
            m.transaction('rollback', NEW, DIGEST, '123-1')
        self.assertEqual(current.resolve(), foreign)

    def test_pending_database_refresh_blocks_code_publication(self):
        (self.deployment / '.database-refresh-pending.json').write_text('pending refresh')
        with self.assertRaises(RuntimeError):
            self.begin()
        self.assertEqual((self.deployment / 'current').resolve(), self.old)

    def test_existing_unowned_candidate_is_preserved(self):
        candidate = self.release(NEW)
        with self.assertRaises(RuntimeError):
            self.begin()
        self.assertTrue(candidate.exists())
        self.preserved()


if __name__ == '__main__':
    unittest.main()
