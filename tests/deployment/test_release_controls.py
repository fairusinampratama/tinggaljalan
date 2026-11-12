"""Exercise production eligibility across GitHub event/input combinations.

No SSH credentials or hosting access are used. The expression evaluator rejects
unknown syntax rather than silently overlooking a newly introduced condition.
"""
import ast
import itertools
from pathlib import Path
import unittest

import yaml

ROOT = Path(__file__).resolve().parents[2]


def eligible(expression, event, publish, ref, enabled):
    values = {
        'github.event_name': event,
        'inputs.publish_production': publish,
        'github.ref': ref,
        'vars.PRODUCTION_DEPLOY_ENABLED': enabled,
    }
    for key, value in values.items():
        expression = expression.replace(key, repr(value))
    expression = expression.replace('&&', ' and ').replace('||', ' or ')
    parsed = ast.parse(expression, mode='eval')
    allowed = (ast.Expression, ast.BoolOp, ast.And, ast.Or, ast.Compare,
               ast.Eq, ast.Constant, ast.Load)
    if any(not isinstance(node, allowed) for node in ast.walk(parsed)):
        raise ValueError('Unreviewed eligibility syntax')
    return bool(eval(compile(parsed, '<eligibility>', 'eval'), {'__builtins__': {}}))


class ReleaseControlTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.workflow = yaml.load(
            (ROOT / '.github/workflows/ci-deploy.yml').read_text(),
            Loader=yaml.BaseLoader,
        )

    def test_only_explicit_manual_main_request_can_package(self):
        condition = self.workflow['jobs']['package-release']['if']
        for event, publish, ref in itertools.product(
            ['push', 'pull_request', 'workflow_dispatch', 'schedule'],
            [False, True], ['refs/heads/main', 'refs/heads/feature', 'refs/tags/main'],
        ):
            with self.subTest(event=event, publish=publish, ref=ref):
                self.assertEqual(eligible(condition, event, publish, ref, 'true'),
                                 event == 'workflow_dispatch' and publish and ref == 'refs/heads/main')

    def test_production_requires_both_explicit_request_and_kill_switch(self):
        condition = self.workflow['jobs']['deploy']['if']
        for event, publish, ref, enabled in itertools.product(
            ['push', 'pull_request', 'workflow_dispatch'], [False, True],
            ['refs/heads/main', 'refs/heads/feature'], ['true', 'false', ''],
        ):
            with self.subTest(event=event, publish=publish, ref=ref, enabled=enabled):
                self.assertEqual(eligible(condition, event, publish, ref, enabled),
                                 event == 'workflow_dispatch' and publish
                                 and ref == 'refs/heads/main' and enabled == 'true')

    def test_dispatch_defaults_to_validation_only(self):
        request = self.workflow['on']['workflow_dispatch']['inputs']['publish_production']
        self.assertEqual(request['type'], 'boolean')
        self.assertEqual(request['default'], 'false')

    def test_production_secrets_stay_behind_environment_gate(self):
        deploy = self.workflow['jobs']['deploy']
        self.assertEqual(deploy['environment'], 'production')
        self.assertEqual(deploy['needs'], 'package-release')
        self.assertEqual(deploy['concurrency']['group'], 'hosting-account')
        self.assertEqual(deploy['concurrency']['cancel-in-progress'], 'false')
        package = self.workflow['jobs']['package-release']
        self.assertEqual(set(package['needs']), {'frontend-build', 'php-tests', 'browser-tests'})
        for name, job in self.workflow['jobs'].items():
            if name != 'deploy':
                self.assertNotIn('secrets.PROD_', str(job))

    def test_new_pushes_cancel_only_obsolete_pr_validation(self):
        concurrency = self.workflow['concurrency']
        self.assertEqual(concurrency['cancel-in-progress'], "${{ github.event_name == 'pull_request' }}")
        self.assertIn('github.event.pull_request.number || github.run_id', concurrency['group'])

    def test_browser_failure_evidence_is_retained(self):
        steps = self.workflow['jobs']['browser-tests']['steps']
        evidence = next(step for step in steps if step.get('name') == 'Upload browser failure evidence')
        self.assertEqual(evidence['if'], 'failure()')
        self.assertIn('test-results', evidence['with']['path'])
        self.assertEqual(evidence['with']['retention-days'], '7')


if __name__ == '__main__':
    unittest.main()
