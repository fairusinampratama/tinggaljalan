#!/usr/bin/env python3
"""Keep preview code rollback available until hosted browser verification passes."""
import argparse
import contextlib
import fcntl
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys

ACCOUNT = Path('/home/u304629909')
PHP = '/opt/alt/php84/usr/bin/php'
SHA = re.compile(r'[0-9a-f]{40}')
DIGEST = re.compile(r'[0-9a-f]{64}')


def require(condition, message):
    if not condition:
        raise RuntimeError(message)


@contextlib.contextmanager
def lock(path):
    require(path.parent.resolve() == path.parent and not path.is_symlink(), 'Unsafe operation lock')
    descriptor = os.open(str(path), os.O_CREAT | os.O_RDWR | os.O_NOFOLLOW, 0o600)
    try:
        fcntl.flock(descriptor, fcntl.LOCK_EX | fcntl.LOCK_NB)
        yield
    finally:
        os.close(descriptor)


def layout():
    root = ACCOUNT / 'domains/preview.tinggaljalan.com'
    deployment = root / 'deployments'
    for path in (ACCOUNT, root, deployment, deployment / 'releases',
                 deployment / 'shared', root / 'public_html'):
        require(path.is_dir() and path.resolve() == path, 'Unexpected preview layout')
    require((root / '.tinggaljalan-staging').read_text().strip() == 'preview.tinggaljalan.com',
            'Preview identity marker is missing')
    return root, deployment


def active_revision(deployment):
    current = deployment / 'current'
    require(current.is_symlink(), 'Preview current must be a symlink')
    active = current.resolve()
    require(active.parent == deployment / 'releases' and SHA.fullmatch(active.name),
            'Preview current points outside releases')
    require(not active.is_symlink() and (active / 'REVISION').read_text().strip() == active.name
            and (active / 'vendor/autoload.php').is_file(), 'Preview release is incomplete')
    return active.name


def write_private(path, record):
    require(not path.exists() and not path.is_symlink(), 'Pending preview recovery must run first')
    descriptor = os.open(str(path), os.O_CREAT | os.O_EXCL | os.O_WRONLY | os.O_NOFOLLOW, 0o600)
    with os.fdopen(descriptor, 'w') as stream:
        json.dump(record, stream)
        stream.flush()
        os.fsync(stream.fileno())


def transaction(operation, source, digest, run):
    require(SHA.fullmatch(source) and DIGEST.fullmatch(digest)
            and re.fullmatch(r'[0-9]+-[0-9]+', run), 'Invalid preview operation identity')
    root, deployment = layout()
    pending = deployment / '.preview-release-pending.json'
    with lock(ACCOUNT / '.tinggaljalan-maintenance.lock'), lock(deployment / '.lock'):
        require(not pending.is_symlink(), 'Unsafe preview journal')
        require(not (deployment / '.database-refresh-pending.json').exists(),
                'Recover pending database refresh before publishing code')
        active = active_revision(deployment)
        if operation == 'begin':
            require(not pending.exists(), 'Recover pending preview publication first')
            release = deployment / 'releases' / source
            require(active != source and not release.exists() and not release.is_symlink(),
                    'Candidate revision already exists; use its existing verified run or a new commit')
            htaccess = root / 'public_html/.htaccess'
            require(htaccess.is_file() and not htaccess.is_symlink(), 'Preview access protection is missing')
            rules = htaccess.read_text()
            require('Require valid-user' in rules and '503' not in rules,
                    'Preview is not ready for a new publication')
            require((root / ('public_html/index-' + active + '.php')).is_file(),
                    'Previous preview entry point is missing')
            write_private(pending, dict(source=source, digest=digest, run=run,
                                        previous=active, htaccess=rules))
            return
        if not pending.exists():
            require(operation == 'rollback', 'No preview publication is pending')
            return
        record = json.loads(pending.read_text())
        require(record.get('source') == source and record.get('digest') == digest
                and record.get('run') == run and SHA.fullmatch(record.get('previous', '')),
                'Preview journal belongs to another operation')
        require(active in (record['previous'], source), 'Another preview revision is active; refusing overwrite')
        if operation == 'finalize':
            require(active == source, 'Candidate is not active')
            require((deployment / 'releases' / source / '.release-archive.sha256').read_text().strip() == digest,
                    'Hosted candidate differs from verified artifact')
            pending.unlink()
            return record['previous']
        previous = deployment / 'releases' / record['previous']
        require(previous.resolve() == previous and (previous / 'vendor/autoload.php').is_file()
                and (previous / 'REVISION').read_text().strip() == record['previous'],
                'Previous preview release is unavailable')
        require('Require valid-user' in record.get('htaccess', ''), 'Previous access protection is invalid')
        if active == source:
            temporary = deployment / 'current.rollback'
            require(not temporary.exists() and not temporary.is_symlink(), 'Ambiguous rollback pointer')
            temporary.symlink_to(previous)
            os.replace(str(temporary), str(deployment / 'current'))
        target = root / 'public_html/.htaccess'
        temporary = root / 'public_html/.htaccess.rollback'
        require(not temporary.exists() and not temporary.is_symlink(), 'Ambiguous access protection file')
        descriptor = os.open(str(temporary), os.O_CREAT | os.O_EXCL | os.O_WRONLY | os.O_NOFOLLOW, 0o644)
        with os.fdopen(descriptor, 'w') as stream:
            stream.write(record['htaccess'])
        os.replace(str(temporary), str(target))
        # Shared compiled views may belong to the candidate; rebuild old views.
        for command in ('optimize:clear', 'config:cache', 'event:cache', 'route:cache', 'view:cache', 'up'):
            subprocess.run([PHP, str(previous / 'artisan'), command], check=True,
                           stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        candidate = deployment / 'releases' / source
        require((deployment / 'current').resolve() != candidate and not candidate.is_symlink(),
                'Candidate cleanup is unsafe')
        if candidate.exists():
            require(shutil.rmtree.avoids_symlink_attacks, 'Safe recursive deletion is unavailable')
            shutil.rmtree(str(candidate))
        pending.unlink()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('operation', choices=['begin', 'finalize', 'rollback', 'recover'])
    parser.add_argument('source', nargs='?', default='')
    parser.add_argument('digest', nargs='?', default='')
    parser.add_argument('run', nargs='?', default='')
    args = parser.parse_args()
    if args.operation == 'recover':
        _, deployment = layout()
        pending = deployment / '.preview-release-pending.json'
        require(not pending.is_symlink(), 'Unsafe preview journal')
        if pending.exists():
            record = json.loads(pending.read_text())
            transaction('rollback', record['source'], record['digest'], record['run'])
    else:
        previous = transaction(args.operation, args.source, args.digest, args.run)
        if args.operation == 'finalize':
            print(json.dumps(dict(previous_sha=previous)))
            return
    print('Preview publication state: ' + args.operation + ' passed. No database restore was performed.')


if __name__ == '__main__':
    try:
        main()
    except Exception as error:
        print('Preview publication refused: ' + (str(error) if isinstance(error, RuntimeError)
                                                else type(error).__name__), file=sys.stderr)
        raise SystemExit(1)
