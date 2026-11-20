import { test } from 'node:test';
import assert from 'node:assert/strict';
import { EventEmitter } from 'node:events';
import { attachStartupDiagnostics, startupErrorCategory } from '../../scripts/testing/preview-startup-diagnostics.mjs';

test('startup evidence classifies failures without exposing messages, URLs or credentials', () => {
    const page = new EventEmitter();
    const evidence = attachStartupDiagnostics(page);
    const secret = 'PRIVATE-CANARY-review-password-and-row-value';
    page.emit('console', { type: () => 'error', text: () => 'TinggalJalan could not start; server content remains available. Initial translations unavailable ' + secret });
    page.emit('pageerror', new Error('Cannot read properties of null ' + secret));
    page.emit('pageerror', new Error('Failed to fetch dynamically imported module: https://private.invalid/' + secret));
    page.emit('requestfailed', { resourceType: () => 'script', url: () => secret });
    page.emit('response', { request: () => ({ resourceType: () => 'script' }), status: () => 401, url: () => secret });
    assert.deepEqual(evidence(), {
        'asset:script:401': 1,
        'console:missing-initial-translations': 1,
        'pageerror:invalid-render-data': 1,
        'pageerror:module-loading': 1,
        'requestfailed:script': 1,
        'startup:bootstrap-failed': 1,
    });
    assert.equal(JSON.stringify(evidence()).includes(secret), false);
    assert.equal(startupErrorCategory(new Error(secret)), 'other');
    assert.equal(startupErrorCategory('Minified React error #418; private-url'), 'react-418');
});

test('successful assets and repeated failures produce bounded fixed counters', () => {
    const page = new EventEmitter();
    const evidence = attachStartupDiagnostics(page);
    for (let index = 0; index < 100; index++) page.emit('pageerror', new Error('unknown-' + index));
    page.emit('response', { request: () => ({ resourceType: () => 'script' }), status: () => 200 });
    page.emit('response', { request: () => ({ resourceType: () => 'fetch' }), status: () => 500 });
    assert.deepEqual(evidence(), { 'asset:script:200': 1, 'pageerror:other': 100 });
});
