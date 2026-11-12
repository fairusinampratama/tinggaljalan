"""Exercise publish eligibility and workflow trust boundaries without hosting access."""
import ast
import itertools
from pathlib import Path
import unittest

import yaml

ROOT = Path(__file__).resolve().parents[2]


def workflow(name):
    return yaml.load((ROOT / '.github/workflows' / name).read_text(), Loader=yaml.BaseLoader)


def eligible(expression, event, ref, enabled='true', build=False):
    for key, value in {'github.event_name': event, 'github.ref': ref,
                       'vars.PRODUCTION_DEPLOY_ENABLED': enabled,
                       'inputs.build_candidate': build}.items():
        expression = expression.replace(key, repr(value))
    expression = expression.replace('&&', ' and ').replace('||', ' or ')
    parsed = ast.parse(expression, mode='eval')
    allowed = (ast.Expression, ast.BoolOp, ast.And, ast.Or, ast.Compare, ast.Eq, ast.Constant, ast.Load)
    if any(not isinstance(node, allowed) for node in ast.walk(parsed)):
        raise ValueError('Unreviewed eligibility syntax')
    return bool(eval(compile(parsed, '<eligibility>', 'eval'), {'__builtins__': {}}))


class ReleaseControlTest(unittest.TestCase):
    def test_ci_never_deploys_or_receives_hosting_credentials(self):
        ci = workflow('ci-deploy.yml')
        self.assertEqual(set(ci['jobs']), {'frontend-build', 'php-tests', 'browser-tests', 'package-release'})
        self.assertNotIn('${{ secrets.', str(ci))
        for job in ci['jobs'].values():
            self.assertNotIn('environment', job)
        self.assertNotIn('build_candidate', ci['on']['workflow_dispatch'] or {})
        self.assertEqual(ci['on']['workflow_call']['inputs']['build_candidate']['type'], 'boolean')
        self.assertEqual(set(ci['jobs']['package-release']['needs']),
                         {'frontend-build', 'php-tests', 'browser-tests'})

    def test_only_explicit_candidate_call_packages_after_checks(self):
        expression = workflow('ci-deploy.yml')['jobs']['package-release']['if']
        for event, build in itertools.product(['push', 'pull_request', 'workflow_dispatch', 'schedule'], [False, True]):
            self.assertEqual(eligible(expression, event, 'refs/heads/main', build=build),
                             event == 'workflow_dispatch' and build)

    def test_production_requires_manual_main_request_and_enable_switch(self):
        promotion = workflow('promote-preview-release.yml')
        self.assertEqual(set(promotion['on']), {'workflow_dispatch'})
        deploy = promotion['jobs']['deploy']
        for event, ref, enabled in itertools.product(['push', 'pull_request', 'workflow_dispatch'],
                ['refs/heads/main', 'refs/heads/feature', 'refs/tags/main'], ['true', 'false', '']):
            self.assertEqual(eligible(deploy['if'], event, ref, enabled),
                             event == 'workflow_dispatch' and ref == 'refs/heads/main' and enabled == 'true')
        self.assertEqual(deploy['environment'], 'production')
        self.assertEqual(deploy['needs'], 'evidence')
        self.assertEqual(deploy['concurrency'], {'group': 'hosting-account', 'cancel-in-progress': 'false'})
        self.assertNotIn('secrets.PROD_', str(promotion['jobs']['evidence']))

    def test_promote_downloads_original_artifact_without_rebuilding(self):
        deploy = workflow('promote-preview-release.yml')['jobs']['deploy']
        download = next(step for step in deploy['steps'] if step.get('name') == 'Download immutable release')
        self.assertEqual(download['with']['run-id'], '${{ inputs.preview_run_id }}')
        self.assertEqual(download['with']['name'], '${{ needs.evidence.outputs.artifact_name }}')
        commands = '\n'.join(step.get('run', '') for step in deploy['steps'])
        self.assertNotIn('npm ', commands)
        self.assertNotIn('composer install', commands)
        self.assertNotIn('build-release.sh', commands)
        self.assertIn('--policy-only', commands)
        self.assertIn(' hosted ', commands)
        self.assertIn('$ARCHIVE_SHA256', commands)

    def test_preview_controller_is_main_only_and_functional_checks_are_mandatory(self):
        preview = workflow('prepare-preview-release.yml')
        self.assertEqual(preview['jobs']['select']['if'], "github.ref == 'refs/heads/main'")
        self.assertEqual(preview['jobs']['candidate']['uses'], './.github/workflows/ci-deploy.yml')
        self.assertEqual(preview['jobs']['candidate']['with']['build_candidate'], 'true')
        self.assertNotIn('functional_checks', str(preview['on']))
        publication = preview['jobs']['preview']
        commands = '\n'.join(step.get('run', '') for step in publication['steps'])
        for helper in ('staging-browser-smoke.mjs', 'staging-media-browser.mjs', 'staging-functional-browser.mjs'):
            self.assertIn(helper, commands)
        self.assertIn('rollback', commands)
        receipt = next(step for step in publication['steps'] if step.get('name') == 'Save promotion evidence after every required check succeeds')
        self.assertNotIn('if', receipt)  # Default success(), never always().
        for job in ('recover', 'preview'):
            self.assertEqual(preview['jobs'][job]['concurrency'],
                             {'group': 'hosting-account', 'cancel-in-progress': 'false'})

    def test_old_staging_entry_delegates_to_current_controller(self):
        staging = workflow('staging-deploy.yml')
        self.assertEqual(set(staging['jobs']), {'preview'})
        self.assertEqual(staging['jobs']['preview']['uses'], './.github/workflows/prepare-preview-release.yml')
        self.assertNotIn('secrets.', str(staging))

    def test_only_obsolete_pr_validation_is_cancelled(self):
        ci = workflow('ci-deploy.yml')
        self.assertEqual(ci['concurrency']['cancel-in-progress'], "${{ github.event_name == 'pull_request' }}")
        self.assertIn('github.event.pull_request.number || github.run_id', ci['concurrency']['group'])
        evidence = next(step for step in ci['jobs']['browser-tests']['steps'] if step.get('name') == 'Upload browser failure evidence')
        self.assertEqual(evidence['if'], 'failure()')
        self.assertIn('test-results', evidence['with']['path'])

    def test_approval_probe_has_no_hosting_operations_or_credentials(self):
        probe = workflow('release-approval-check.yml')
        self.assertEqual(set(probe['on']), {'workflow_dispatch'})
        approval = probe['jobs']['approval']
        self.assertEqual(approval['if'], "github.ref == 'refs/heads/main'")
        self.assertEqual(approval['environment'], {'name': 'production', 'deployment': 'false'})
        self.assertEqual(len(approval['steps']), 1)
        self.assertNotIn('uses', approval['steps'][0])
        self.assertNotIn('${{', approval['steps'][0]['run'])
        self.assertTrue(all(line.strip().startswith('echo ')
                            for line in approval['steps'][0]['run'].splitlines()))


if __name__ == '__main__':
    unittest.main()
