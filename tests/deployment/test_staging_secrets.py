import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[2] / 'scripts/deployment/prepare-staging-secrets.py'
spec = importlib.util.spec_from_file_location('secrets_helper', SCRIPT)
helper = importlib.util.module_from_spec(spec)
spec.loader.exec_module(helper)


class SecretPreparationTest(unittest.TestCase):
    def test_collects_all_field_errors(self):
        errors = helper.validate({'review_password': 'short', 'admin_password': 'short'})
        self.assertEqual(len(errors), 3)
        self.assertNotIn('short', '\n'.join(errors))

    def test_existing_database_password_and_unique_long_passwords(self):
        self.assertEqual(helper.validate({'db_password': 'existing-db', 'review_password': 'r' * 16, 'admin_password': 'a' * 16}), [])

    def test_duplicates_controls_and_bcrypt_length(self):
        self.assertTrue(helper.validate(dict.fromkeys(helper.FIELDS, 'x' * 16)))
        self.assertTrue(helper.validate({'db_password': 'bad\n', 'review_password': 'r' * 65, 'admin_password': 'a' * 16}))

    def test_special_characters_are_encoded_privately_and_never_logged(self):
        values = {'db_password': 'db"\\${HOME}', 'review_password': 'review"\\$' + 'r' * 16, 'admin_password': 'admin' + 'a' * 16}
        environment = {**os.environ, **{name: values[field] for field, name in helper.FIELDS.items()}}
        with tempfile.TemporaryDirectory() as directory:
            output = Path(directory) / 'config.json'
            result = subprocess.run([sys.executable, str(SCRIPT), str(output)], env=environment, capture_output=True, text=True)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(json.loads(output.read_text()), values)
            self.assertEqual(output.stat().st_mode & 0o777, 0o600)
            for value in values.values():
                self.assertNotIn(value, result.stdout + result.stderr)
            result = subprocess.run([sys.executable, str(SCRIPT), str(output)], env=environment, capture_output=True)
            self.assertNotEqual(result.returncode, 0)


if __name__ == '__main__':
    unittest.main()
