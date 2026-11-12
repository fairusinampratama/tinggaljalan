#!/usr/bin/env python3
"""Validate release evidence from fixed-repository GitHub runs; never deploy."""
import argparse
import base64
import hashlib
import json
import os
from pathlib import Path
import re
import tarfile
import urllib.request

REPOSITORY = 'fairusinampratama/tinggaljalan'
SHA = re.compile(r'[0-9a-f]{40}')
DIGEST = re.compile(r'[0-9a-f]{64}')
CHECKS = {'runtime', 'desktop', 'mobile', 'booking', 'admin', 'media', 'integration_isolation'}
POLICY_FILES = (
    '.github/workflows/prepare-preview-release.yml',
    '.github/workflows/ci-deploy.yml',
    '.github/workflows/promote-preview-release.yml',
    'scripts/deployment/deploy-hostinger.sh',
    'scripts/deployment/rollback-hostinger.sh',
    '.github/actions/preview-access/action.yml',
    'scripts/deployment/hosting-maintenance.py',
    'scripts/deployment/configure-staging.php',
    'scripts/deployment/staging-config.php',
    '.env.staging.example',
    'scripts/deployment/release-provenance.py',
    'scripts/deployment/preview-release-state.py',
    'scripts/deployment/deploy-staging.sh',
    'scripts/testing/staging-browser-smoke.mjs',
    'scripts/testing/public-review-routes.mjs',
    'scripts/testing/staging-functional-browser.mjs',
    'scripts/testing/staging-functional-guard.php',
    'scripts/testing/staging-media-browser.mjs',
)


def require(condition, message):
    if not condition:
        raise RuntimeError(message)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, message, headers, newurl):
        # Never forward GitHub or preview credentials to a redirect target.
        return None


def api(path):
    require(path.startswith('/repos/' + REPOSITORY + '/'), 'Unexpected repository API path')
    request = urllib.request.Request('https://api.github.com' + path, headers={
        'Authorization': 'Bearer ' + os.environ['GH_TOKEN'],
        'Accept': 'application/vnd.github+json',
        'X-GitHub-Api-Version': '2022-11-28',
    })
    with urllib.request.build_opener(NoRedirect).open(request, timeout=30) as response:
        return json.load(response)


def repo(path):
    return '/repos/' + REPOSITORY + path


def validate_metadata(metadata, source):
    require(isinstance(metadata, dict) and metadata.get('format_version') == 1,
            'Unreviewed candidate metadata format')
    require(isinstance(source, str) and SHA.fullmatch(source) and metadata.get('source_sha') == source,
            'Candidate source revision differs')
    require(isinstance(metadata.get('archive_sha256'), str)
            and DIGEST.fullmatch(metadata['archive_sha256']), 'Invalid archive digest')
    return metadata


def validate_archive(directory, source):
    directory = Path(directory)
    metadata = validate_metadata(json.loads((directory / 'candidate.json').read_text()), source)
    archive = directory / ('release-' + source + '.tar.gz')
    digest = hashlib.sha256()
    with archive.open('rb') as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b''):
            digest.update(chunk)
    require(digest.hexdigest() == metadata['archive_sha256'], 'Candidate archive integrity failed')
    revision = None
    with tarfile.open(str(archive), 'r:gz') as release:
        for member in release:
            parts = Path(member.name).parts
            require(not member.name.startswith('/') and '..' not in parts,
                    'Unsafe release archive path')
            require(not member.isdev() and not member.issym() and not member.islnk(),
                    'Release archive must contain regular files/directories only')
            require('.env' not in parts, 'Environment file found in candidate')
            if member.name.lstrip('./') == 'REVISION':
                require(member.isfile() and member.size <= 100, 'Invalid release revision file')
                revision = release.extractfile(member).read().decode().strip()
    require(revision == source, 'Archive revision differs from tested source')
    return metadata


def validate_receipt(receipt, run_id):
    require(isinstance(receipt, dict) and receipt.get('format_version') == 1,
            'Unreviewed preview receipt format')
    require(str(receipt.get('preview_run_id')) == str(run_id), 'Receipt belongs to another run')
    validate_metadata(receipt, receipt.get('source_sha', ''))
    require(isinstance(receipt.get('controller_sha'), str)
            and SHA.fullmatch(receipt['controller_sha']), 'Invalid preview controller revision')
    require(set(receipt.get('checks', {})) == CHECKS
            and all(value is True for value in receipt['checks'].values()),
            'Mandatory preview checks are incomplete')
    return receipt


def validate_run(run, run_id):
    require(str(run.get('id')) == str(run_id), 'Unexpected workflow run')
    require(run.get('repository', {}).get('full_name') == REPOSITORY, 'Unexpected source repository')
    require(run.get('path') == '.github/workflows/prepare-preview-release.yml'
            and run.get('event') == 'workflow_dispatch' and run.get('head_branch') == 'main',
            'Preview evidence must come from the manual main controller')
    require(run.get('status') == 'completed' and run.get('conclusion') == 'success',
            'Preview workflow did not complete successfully')


def approve_candidate(receipt, run_id, get=api):
    require(re.fullmatch(r'[1-9][0-9]*', str(run_id)), 'A numeric preview run ID is required')
    validate_receipt(receipt, run_id)
    run = get(repo('/actions/runs/' + str(run_id)))
    validate_run(run, run_id)
    require(run.get('head_sha') == receipt['controller_sha'], 'Receipt controller differs from run')
    source = receipt['source_sha']
    main = get(repo('/commits/main'))
    commit = get(repo('/commits/' + source))
    require(commit['commit']['tree']['sha'] == main['commit']['tree']['sha'],
            'Candidate content differs from current main; prepare a new preview candidate')
    comparison = get(repo('/compare/' + source + '...' + main['sha']))
    require(comparison.get('status') in ('ahead', 'identical')
            and comparison.get('behind_by') == 0, 'Candidate commits are not preserved in main')
    for path in POLICY_FILES:
        tested = get(repo('/contents/' + path + '?ref=' + receipt['controller_sha']))
        current = get(repo('/contents/' + path + '?ref=' + main['sha']))
        require(tested.get('sha') and tested['sha'] == current.get('sha'),
                'Preview verification policy changed; prepare fresh evidence')
    artifact_name = 'candidate-' + source
    artifacts = get(repo('/actions/runs/' + str(run_id) + '/artifacts?per_page=100'))
    matches = [item for item in artifacts['artifacts'] if item.get('name') == artifact_name]
    require(len(matches) == 1 and matches[0].get('expired') is False,
            'The original immutable candidate artifact is missing or expired')
    require(re.fullmatch(r'sha256:[0-9a-f]{64}', matches[0].get('digest', '')),
            'GitHub artifact digest is missing')
    return dict(source_sha=source, archive_sha256=receipt['archive_sha256'],
                artifact_name=artifact_name, artifact_id=matches[0]['id'],
                artifact_digest=matches[0]['digest'], preview_run_id=str(run_id))


def check_current_preview(source):
    password = os.environ['STAGING_REVIEW_PASSWORD']
    authorization = base64.b64encode(('reviewer:' + password).encode()).decode()
    request = urllib.request.Request('https://preview.tinggaljalan.com/up', headers={
        'Authorization': 'Basic ' + authorization, 'Cache-Control': 'no-cache',
    })
    with urllib.request.build_opener(NoRedirect).open(request, timeout=30) as response:
        health = json.load(response)
    require(health.get('status') == 'up' and health.get('revision') == source,
            'Preview no longer serves the reviewed candidate')


def emit(values):
    with open(os.environ['GITHUB_OUTPUT'], 'a') as output:
        for key, value in values.items():
            output.write(key + '=' + str(value) + '\n')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='command')
    sub.required = True
    select = sub.add_parser('select')
    select.add_argument('source')
    verify = sub.add_parser('archive')
    verify.add_argument('directory', type=Path)
    verify.add_argument('source')
    receipt = sub.add_parser('receipt')
    receipt.add_argument('directory', type=Path)
    receipt.add_argument('source')
    promotion = sub.add_parser('promotion')
    promotion.add_argument('receipt', type=Path)
    promotion.add_argument('run_id')
    promotion.add_argument('--policy-only', action='store_true')
    hosted = sub.add_parser('hosted')
    hosted.add_argument('source')
    hosted.add_argument('digest')
    args = parser.parse_args()
    require(os.environ.get('GITHUB_REPOSITORY') == REPOSITORY, 'Unexpected controller repository')
    if args.command == 'select':
        require(SHA.fullmatch(args.source), 'A full source revision is required')
        commit = api(repo('/commits/' + args.source))
        require(commit.get('sha') == args.source, 'Source revision does not exist in repository')
        controller = os.environ['GITHUB_SHA']
        require(SHA.fullmatch(controller), 'Invalid controller revision')
        for path in ('scripts/deployment/configure-staging.php', 'scripts/deployment/staging-config.php',
                     'scripts/deployment/hosting-maintenance.py', '.env.staging.example'):
            candidate = api(repo('/contents/' + path + '?ref=' + args.source))
            trusted = api(repo('/contents/' + path + '?ref=' + controller))
            require(candidate.get('sha') and candidate['sha'] == trusted.get('sha'),
                    'Candidate has outdated hosting/configuration helpers; update it from main first')
        emit(dict(source_sha=args.source))
    elif args.command == 'archive':
        validate_archive(args.directory, args.source)
        print('Immutable candidate archive verified.')
    elif args.command == 'receipt':
        metadata = validate_archive(args.directory, args.source)
        evidence = dict(metadata, preview_run_id=os.environ['GITHUB_RUN_ID'],
                        controller_sha=os.environ['GITHUB_SHA'],
                        checks={name: True for name in sorted(CHECKS)})
        validate_receipt(evidence, os.environ['GITHUB_RUN_ID'])
        (args.directory / 'preview-receipt.json').write_text(json.dumps(evidence))
    elif args.command == 'hosted':
        require(SHA.fullmatch(args.source) and DIGEST.fullmatch(args.digest), 'Invalid hosted candidate identity')
        deployment = Path('/home/u304629909/domains/preview.tinggaljalan.com/deployments')
        release = deployment / 'releases' / args.source
        require((deployment / 'current').is_symlink() and (deployment / 'current').resolve() == release,
                'The reviewed candidate is no longer active on preview')
        require((release / 'REVISION').read_text().strip() == args.source
                and (release / '.release-archive.sha256').read_text().strip() == args.digest,
                'Hosted preview archive identity differs')
        require(not (deployment / '.preview-release-pending.json').exists()
                and not (deployment / '.database-refresh-pending.json').exists(),
                'Preview verification or recovery is pending')
        print('Hosted preview revision and original archive identity verified.')
    else:
        approved = approve_candidate(json.loads(args.receipt.read_text()), args.run_id)
        if not args.policy_only:
            check_current_preview(approved['source_sha'])
        emit(approved)
        print('Main content, trusted preview checks, original artifact and live preview verified.')


if __name__ == '__main__':
    try:
        main()
    except Exception as error:
        # Credentials, HTTP bodies and remote exceptions never enter diagnostics.
        print('Release evidence refused: ' + (str(error) if isinstance(error, RuntimeError)
                                              else type(error).__name__), file=__import__('sys').stderr)
        raise SystemExit(1)
