"""Validate individually masked secrets and optionally write a private transfer file."""
import json
import os
from pathlib import Path
import sys

FIELDS = {
    'db_password': 'STAGING_DB_PASSWORD',
    'review_password': 'STAGING_REVIEW_PASSWORD',
    'admin_password': 'STAGING_ADMIN_PASSWORD',
}


def validate(values):
    errors = []
    for field, name in FIELDS.items():
        value = values.get(field)
        minimum = 1 if field == 'db_password' else 16
        if not isinstance(value, str) or not value:
            errors.append(name + ': missing')
        elif len(value.encode('utf-8')) < minimum:
            errors.append(name + ': requires at least 16 bytes')
        if isinstance(value, str) and any(ord(c) < 32 or ord(c) == 127 for c in value):
            errors.append(name + ': control characters are unsupported')
        if field != 'db_password' and isinstance(value, str) and len(value.encode('utf-8')) > 64:
            errors.append(name + ': maximum 64 bytes')
    if all(isinstance(values.get(field), str) and values[field] for field in FIELDS):
        if len(set(values[field] for field in FIELDS)) != 3:
            errors.append('Use different database, review, and admin passwords')
    return errors


if __name__ == '__main__':
    values = {field: os.environ.get(name, '') for field, name in FIELDS.items()}
    errors = validate(values)
    if errors:
        sys.exit('Staging credentials failed validation:\n' + '\n'.join(errors))
    if len(sys.argv) > 1:
        path = Path(sys.argv[1])
        path.parent.mkdir(parents=True, exist_ok=True, mode=0o700)
        # Reject symlinks and existing files; never overwrite an unexpected destination.
        fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
        with os.fdopen(fd, 'w') as stream:
            json.dump(values, stream)
    print('All staging credential checks passed. Values were not logged.')
