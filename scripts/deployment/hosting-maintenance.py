#!/usr/bin/env python3
"""Account capacity and bounded release retention. No Laravel boot or DB access."""
import argparse
import contextlib
import fcntl
import hashlib
import io
import json
import os
from pathlib import Path
import re
import shutil
import stat
import subprocess
import sys
import tarfile
import time

ACCOUNT = Path('/home/u304629909')
DOMAINS = {'production': 'tinggaljalan.com', 'preview': 'preview.tinggaljalan.com'}
SHA = re.compile(r'[0-9a-f]{40}')
RESERVE_FILES = 25000
RESERVE_BYTES = 512 * 1024**2
# Operational budgets, NOT claims about the provider's undocumented hard quota.
FILE_BUDGET = 350000
BYTE_BUDGET = 10 * 1024**3
GRACE_SECONDS = 3600


def require(condition, message):
    if not condition:
        raise RuntimeError(message)


def canonical_directory(path):
    require(path.is_dir() and path.resolve() == path, 'Noncanonical or missing directory')


@contextlib.contextmanager
def lock(path, inherited=None):
    canonical_directory(path.parent)
    require(not path.is_symlink(), 'Symlink lock refused')
    if inherited is not None:
        require(path.exists() and os.fstat(inherited).st_ino == path.stat().st_ino
                and os.fstat(inherited).st_dev == path.stat().st_dev, 'Invalid inherited lock')
        # flock ownership follows the inherited open-file description.
        fcntl.flock(inherited, fcntl.LOCK_EX | fcntl.LOCK_NB)
        yield
        return
    descriptor = os.open(str(path), os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW, 0o600)
    try:
        fcntl.flock(descriptor, fcntl.LOCK_EX | fcntl.LOCK_NB)
        yield
    finally:
        os.close(descriptor)


def usage():
    canonical_directory(ACCOUNT)
    values = []
    for options in [['--inodes'], ['--block-size=1']]:
        result = subprocess.run(['du', '-sx', *options, str(ACCOUNT)],
                                stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, universal_newlines=True)
        require(result.returncode == 0, 'Account usage scan incomplete; refusing to assume capacity')
        values.append(int(result.stdout.split()[0]))
    return values


def capacity(reserve=False, alert=False):
    files, size = usage()
    predicted_files = files + (RESERVE_FILES if reserve else 0)
    predicted_bytes = size + (RESERVE_BYTES if reserve else 0)
    # Keep a further 512 MiB of the budget available for sessions/logs/backups.
    allowed = predicted_files <= FILE_BUDGET and predicted_bytes <= BYTE_BUDGET - RESERVE_BYTES
    warning = files >= FILE_BUDGET * .8 or size >= BYTE_BUDGET * .8
    result = dict(files=files, allocated_bytes=size, file_budget=FILE_BUDGET,
                  byte_budget=BYTE_BUDGET, provider_quota_verified=False,
                  reserve_files=RESERVE_FILES if reserve else 0,
                  reserve_bytes=RESERVE_BYTES if reserve else 0,
                  warning=warning, allowed=allowed)
    print(json.dumps(result), flush=True)
    require(allowed, 'Account safety budget exceeded; clean obsolete releases before deployment')
    require(not (alert and warning), 'Account capacity warning threshold reached (80% of safety budget)')
    return result


def archive_budget(path):
    files = size = 0
    with tarfile.open(path, 'r:gz') as archive:
        for member in archive:
            files += 1
            size += ((member.size + 4095) // 4096) * 4096 + 4096
            require(not member.isdev() and '..' not in Path(member.name).parts
                    and not member.name.startswith('/'), 'Unsafe archive member')
    require(files + 1000 <= RESERVE_FILES and size <= RESERVE_BYTES,
            'Release exceeds tested deployment reservation; review account budgets before increasing it')
    print(json.dumps(dict(archive_entries=files, estimated_allocated_bytes=size)))


def release_valid(release, shared):
    canonical_directory(release)
    revision = release / 'REVISION'
    require(revision.is_file() and not revision.is_symlink()
            and revision.read_text().strip() == release.name, 'Release identity mismatch')
    # Mutable production/preview state must exist only in shared locations.
    for name, target in [('.env', shared / '.env'), ('storage', shared / 'storage'),
                         ('public/storage', shared / 'storage/app/public')]:
        link = release / name
        require(link.is_symlink() and link.resolve() == target, 'Unreviewed release-local mutable state')
    for base, dirs, names in os.walk(release, followlinks=False):
        for name in dirs + names:
            item = Path(base) / name
            mode = item.lstat().st_mode
            require(item.lstat().st_dev == release.stat().st_dev, 'Cross-filesystem release refused')
            require(stat.S_ISREG(mode) or stat.S_ISDIR(mode) or stat.S_ISLNK(mode),
                    'Special release entry refused')
            # These do not belong in an immutable release, even if someone copied them there.
            require(not (name.endswith(('.sql', '.sql.gz', '.sqlite', '.sqlite3'))
                         or (name.startswith('.env.') and name not in ('.env.example', '.env.prod-preview.example', '.env.staging.example'))
                         or name in ('backups', '.htpasswd')),
                    'Unexpected private state in release; manual review required')


def plan(environment, previous=''):
    root = ACCOUNT / 'domains' / DOMAINS[environment]
    deployments = root / 'deployments'
    releases = deployments / 'releases'
    shared = deployments / 'shared'
    for directory in (root, deployments, releases, shared):
        canonical_directory(directory)
    current = deployments / 'current'
    require(current.is_symlink(), 'Current release must be a symlink')
    active = current.resolve()
    require(active.parent == releases and SHA.fullmatch(active.name), 'Current points outside releases')
    release_valid(active, shared)
    require((active / 'vendor/autoload.php').is_file()
            and (active / 'public/build/manifest.json').is_file(), 'Current release incomplete')
    records = []
    for path in sorted(releases.iterdir()):
        if not SHA.fullmatch(path.name):
            records.append(dict(sha='[unrecognized]', action='keep', reason='unrecognized entry'))
            continue
        try:
            release_valid(path, shared)
        except (RuntimeError, OSError, UnicodeError):
            records.append(dict(sha=path.name, action='keep', reason='requires manual review'))
            continue
        records.append(dict(sha=path.name, action='candidate', reason='obsolete immutable release',
                            mtime=(path / 'REVISION').stat().st_mtime,
                            complete=(path / 'vendor/autoload.php').is_file()
                            and (path / 'public/build/manifest.json').is_file()))
    valid = {r['sha']: r for r in records if r['action'] == 'candidate'}
    keep = {active.name}
    if previous:
        require(SHA.fullmatch(previous) and previous in valid and valid[previous]['complete'],
                'Previous rollback release is invalid')
        keep.add(previous)
    complete = sorted((r for r in valid.values() if r['complete'] and r['sha'] != active.name),
                      key=lambda r: (r['mtime'], r['sha']), reverse=True)
    for record in complete:
        if len(keep) < 3:
            keep.add(record['sha'])
    require(len(keep) >= min(3, len(complete) + 1), 'Insufficient rollback retention')
    for record in valid.values():
        if record['sha'] in keep:
            record.update(action='keep', reason='active' if record['sha'] == active.name else 'rollback')
        elif time.time() - record['mtime'] < GRACE_SECONDS:
            record.update(action='keep', reason='one-hour grace period')
        else:
            record['action'] = 'remove'
    return dict(environment=environment, active=active.name, releases=records)


def plan_digest(result):
    return hashlib.sha256(json.dumps(result, sort_keys=True).encode()).hexdigest()


def archive_obsolete(environment):
    """Stream recoverable obsolete immutable code, excluding all runtime/private state."""
    deployment = ACCOUNT / 'domains' / DOMAINS[environment] / 'deployments'
    with lock(deployment / '.lock'):
        result = plan(environment)
        manifest = json.dumps(dict(plan=result, digest=plan_digest(result))).encode()
        with tarfile.open(fileobj=sys.stdout.buffer, mode='w|gz') as archive:
            entry = tarfile.TarInfo('MANIFEST.json')
            entry.size = len(manifest)
            archive.addfile(entry, io.BytesIO(manifest))
            for record in result['releases']:
                if record['action'] != 'remove':
                    continue
                sha = record['sha']
                def safe_member(member):
                    parts = Path(member.name).parts[1:]
                    if (any(part == '.env' or part.startswith('.env.') or part == 'storage'
                            for part in parts) or parts[:2] == ('bootstrap', 'cache')
                            or member.issym() or member.islnk()):
                        return None
                    return member
                archive.add(str(deployment / 'releases' / sha), arcname=sha,
                            recursive=True, filter=safe_member)


def retention(environment, apply=False, previous='', lock_fd=None, expected_plan=''):
    deployment = ACCOUNT / 'domains' / DOMAINS[environment] / 'deployments'
    with lock(deployment / '.lock', lock_fd):
        result = plan(environment, previous)
        if expected_plan:
            require(plan_digest(result) == expected_plan, 'Archived retention plan changed; rerun plan/archive')
        print(json.dumps(result), flush=True)
        if not apply:
            return result
        active = result['active']
        for record in result['releases']:
            if record['action'] != 'remove':
                continue
            # Revalidate the whole policy and current pointer immediately before every removal.
            latest = plan(environment, previous)
            require(latest['active'] == active, 'Active revision changed; cleanup aborted')
            require(any(r['sha'] == record['sha'] and r['action'] == 'remove'
                        for r in latest['releases']), 'Retention plan changed; cleanup aborted')
            path = deployment / 'releases' / record['sha']
            release_valid(path, deployment / 'shared')
            require(shutil.rmtree.avoids_symlink_attacks, 'Safe recursive deletion unavailable')
            shutil.rmtree(path)  # symlinks are unlinked, never followed into shared storage
            print(json.dumps(dict(removed=record['sha'], environment=environment)), flush=True)
        return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='command')
    sub.required = True  # Hostinger's system Python is 3.6.
    check = sub.add_parser('capacity')
    check.add_argument('--reserve', action='store_true')
    check.add_argument('--alert', action='store_true')
    archive = sub.add_parser('archive-budget')
    archive.add_argument('archive', type=Path)
    obsolete = sub.add_parser('archive-obsolete')
    obsolete.add_argument('environment', choices=DOMAINS)
    clean = sub.add_parser('retention')
    clean.add_argument('environment', choices=DOMAINS)
    clean.add_argument('--apply', action='store_true')
    clean.add_argument('--previous', default='')
    clean.add_argument('--lock-fd', type=int)
    clean.add_argument('--account-lock-fd', type=int)
    clean.add_argument('--expected-plan', default='')
    args = parser.parse_args()
    if args.command == 'archive-budget':
        archive_budget(args.archive)
    elif args.command == 'capacity':
        capacity(args.reserve, args.alert)
    elif args.command == 'archive-obsolete':
        with lock(ACCOUNT / '.tinggaljalan-maintenance.lock'):
            archive_obsolete(args.environment)
    else:
        with lock(ACCOUNT / '.tinggaljalan-maintenance.lock', args.account_lock_fd):
            retention(args.environment, args.apply, args.previous, args.lock_fd, args.expected_plan)


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, OSError, ValueError, tarfile.TarError) as error:
        # Never emit filenames from OS exceptions: a private token could be a filename.
        print('Hosting maintenance refused: ' + (str(error) if isinstance(error, RuntimeError)
                                                else type(error).__name__), file=sys.stderr)
        sys.exit(1)
