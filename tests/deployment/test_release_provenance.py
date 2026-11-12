import copy
import hashlib
import importlib.util
import io
import json
from pathlib import Path
import tarfile
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('provenance', ROOT / 'scripts/deployment/release-provenance.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)
SOURCE = 'a' * 40
CONTROLLER = 'b' * 40
MAIN = 'c' * 40


class ProvenanceTest(unittest.TestCase):
    def setUp(self):
        self.receipt = dict(format_version=1, source_sha=SOURCE, controller_sha=CONTROLLER,
                            preview_run_id='123', archive_sha256='d' * 64,
                            checks={key: True for key in m.CHECKS})
        self.run = dict(id=123, repository=dict(full_name=m.REPOSITORY),
                        path='.github/workflows/prepare-preview-release.yml',
                        event='workflow_dispatch', head_branch='main', head_sha=CONTROLLER,
                        status='completed', conclusion='success')
        self.tree = 'e' * 40
        self.artifact = dict(id=99, name='candidate-' + SOURCE, expired=False, digest='sha256:' + 'f' * 64)

    def api(self, path):
        if path.endswith('/actions/runs/123'):
            return self.run
        if '/artifacts?' in path:
            return dict(artifacts=[self.artifact])
        if '/contents/' in path:
            return dict(sha='same-reviewed-policy')
        if '/compare/' in path:
            return dict(status='ahead', behind_by=0)
        if path.endswith('/commits/main'):
            return dict(sha=MAIN, commit=dict(tree=dict(sha=self.tree)))
        if path.endswith('/commits/' + SOURCE):
            return dict(sha=SOURCE, commit=dict(tree=dict(sha='e' * 40)))
        raise AssertionError(path)

    def test_approved_artifact_is_the_original_preview_artifact(self):
        result = m.approve_candidate(self.receipt, '123', self.api)
        self.assertEqual(result['artifact_id'], 99)
        self.assertEqual(result['archive_sha256'], self.receipt['archive_sha256'])
        self.assertEqual(result['source_sha'], SOURCE)

    def test_missing_or_failed_checks_are_rejected(self):
        for check in m.CHECKS:
            for value in (False, 1, 'true'):
                receipt = copy.deepcopy(self.receipt)
                receipt['checks'][check] = value
                with self.assertRaises(RuntimeError):
                    m.approve_candidate(receipt, '123', self.api)
        receipt = copy.deepcopy(self.receipt)
        del receipt['checks']['booking']
        with self.assertRaises(RuntimeError):
            m.approve_candidate(receipt, '123', self.api)

    def test_untrusted_or_incomplete_workflow_runs_are_rejected(self):
        for key, value in [('head_branch', 'feature'), ('event', 'pull_request'),
                           ('path', '.github/workflows/old-preview.yml'),
                           ('conclusion', 'failure'), ('status', 'in_progress'), ('head_sha', MAIN)]:
            with self.subTest(key=key):
                original = self.run[key]
                self.run[key] = value
                with self.assertRaises(RuntimeError):
                    m.approve_candidate(self.receipt, '123', self.api)
                self.run[key] = original

    def test_candidate_must_match_current_main_content(self):
        self.tree = '0' * 40
        with self.assertRaisesRegex(RuntimeError, 'differs from current main'):
            m.approve_candidate(self.receipt, '123', self.api)

    def test_candidate_history_must_be_preserved_in_main(self):
        def get(path):
            if '/compare/' in path:
                return dict(status='diverged', behind_by=2)
            return self.api(path)
        with self.assertRaisesRegex(RuntimeError, 'not preserved'):
            m.approve_candidate(self.receipt, '123', get)

    def test_changed_verification_policy_invalidates_old_receipt(self):
        def get(path):
            if '/contents/' in path and path.endswith('?ref=' + MAIN):
                return dict(sha='new-policy')
            return self.api(path)
        with self.assertRaisesRegex(RuntimeError, 'policy changed'):
            m.approve_candidate(self.receipt, '123', get)

    def test_missing_expired_or_unidentified_artifact_is_rejected(self):
        for key, value in [('expired', True), ('digest', ''), ('name', 'replacement-artifact')]:
            original = self.artifact[key]
            self.artifact[key] = value
            with self.assertRaises(RuntimeError):
                m.approve_candidate(self.receipt, '123', self.api)
            self.artifact[key] = original

    def archive(self, directory, revision=SOURCE, extra=None):
        path = directory / ('release-' + SOURCE + '.tar.gz')
        with tarfile.open(path, 'w:gz') as archive:
            content = revision.encode()
            item = tarfile.TarInfo('./REVISION')
            item.size = len(content)
            archive.addfile(item, io.BytesIO(content))
            if extra:
                archive.addfile(extra, io.BytesIO(b''))
        metadata = dict(format_version=1, source_sha=SOURCE,
                        archive_sha256=hashlib.sha256(path.read_bytes()).hexdigest())
        (directory / 'candidate.json').write_text(json.dumps(metadata))
        return path

    def test_archive_bytes_and_revision_are_bound_to_metadata(self):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            archive = self.archive(directory)
            m.validate_archive(directory, SOURCE)
            archive.write_bytes(archive.read_bytes() + b'tampered')
            with self.assertRaisesRegex(RuntimeError, 'integrity failed'):
                m.validate_archive(directory, SOURCE)
            self.archive(directory, revision=MAIN)
            with self.assertRaisesRegex(RuntimeError, 'revision differs'):
                m.validate_archive(directory, SOURCE)

    def test_archive_cannot_import_environment_or_escape_release_root(self):
        with tempfile.TemporaryDirectory() as temporary:
            for name in ('../outside', '/outside', './nested/.env'):
                self.archive(Path(temporary), extra=tarfile.TarInfo(name))
                with self.assertRaises(RuntimeError):
                    m.validate_archive(temporary, SOURCE)
            item = tarfile.TarInfo('./storage')
            item.type = tarfile.SYMTYPE
            item.linkname = '/production/storage'
            self.archive(Path(temporary), extra=item)
            with self.assertRaises(RuntimeError):
                m.validate_archive(temporary, SOURCE)

    def test_receipt_identity_cannot_be_substituted(self):
        with self.assertRaises(RuntimeError):
            m.approve_candidate(self.receipt, '124', self.api)
        with self.assertRaises(RuntimeError):
            m.approve_candidate(self.receipt, '123;command', self.api)


if __name__ == '__main__':
    unittest.main()
