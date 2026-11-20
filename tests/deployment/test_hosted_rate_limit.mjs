import { test } from 'node:test';
import assert from 'node:assert/strict';
import { EventEmitter } from 'node:events';
import { navigateHostedPreview, retryDelay } from '../../scripts/testing/hosted-rate-limit.mjs';

const target = 'https://preview.tinggaljalan.com/news';
function fixture(statuses, { asset = true, header, failure = false, origin = target } = {}) {
    const page = new EventEmitter();
    page.calls = 0;
    page.goto = async (url, options) => {
        assert.equal(url, target);
        assert.equal(options.waitUntil, 'domcontentloaded');
        const status = statuses[Math.min(page.calls++, statuses.length - 1)];
        page.emit('response', { status: () => status, url: () => origin,
            request: () => ({ resourceType: () => asset ? 'script' : 'document' }),
            headers: () => ({ 'retry-after': header }) });
        if (failure) throw new Error('PRIVATE request failure');
        return { ok: () => asset || status === 200 };
    };
    return page;
}

test('a genuine 429 retries a fresh GET, respects Retry-After, then requires readiness', async () => {
    const page = fixture([429, 200], { header: '12' });
    const delays = [], retries = [];
    let checks = 0;
    await navigateHostedPreview(page, target, { wait: async ms => delays.push(ms), onRetry: item => retries.push(item), ready: async () => { checks++; } });
    assert.equal(page.calls, 2);
    assert.equal(checks, 2);
    assert.deepEqual(delays, [12000]);
    assert.deepEqual(retries, [{ attempt: 1, status: 429, delaySeconds: 12 }]);
    assert.equal(page.listenerCount('response'), 0);
});

test('persistent document throttling fails closed after three GET attempts', async () => {
    const page = fixture([429], { asset: false });
    const delays = [];
    await assert.rejects(navigateHostedPreview(page, target, { wait: async ms => delays.push(ms), ready: async () => {} }));
    assert.equal(page.calls, 3);
    assert.deepEqual(delays, [10000, 20000]);
    assert.equal(page.listenerCount('response'), 0);
});

test('unobservable status, 500, 403, render failures, and third-party 429 never trigger retries', async () => {
    for (const [status, origin] of [[-1, target], [0, target], [500, target], [403, target], [200, target], [429, 'https://other.invalid/private']]) {
        const page = fixture([status], { origin });
        await assert.rejects(navigateHostedPreview(page, target, { wait: async () => assert.fail('unexpected retry'), ready: async () => { throw new Error('render failure'); } }));
        assert.equal(page.calls, 1);
    }
});

test('long cooldowns fail rather than retrying early; dates and invalid headers are bounded', async () => {
    const page = fixture([429], { header: '120' });
    await assert.rejects(navigateHostedPreview(page, target, { wait: async () => assert.fail('early retry'), ready: async () => {} }));
    assert.equal(page.calls, 1);
    assert.equal(retryDelay('Thu, 01 Jan 1970 00:00:30 GMT', 1, 0), 30000);
    assert.equal(retryDelay('PRIVATE-CANARY', 2), 20000);
    await assert.rejects(navigateHostedPreview(page, 'https://other.invalid'), /Unexpected preview origin/);
});
