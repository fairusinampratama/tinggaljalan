import { test, expect } from '@playwright/test';
import { waitForHostedHandoff } from '../../scripts/testing/hosted-browser-readiness.mjs';
import { navigateHostedPreview } from '../../scripts/testing/hosted-rate-limit.mjs';

test('a rate-limited module requires a fresh successful document before acceptance', async ({ page }) => {
    let documentRequests = 0, moduleRequests = 0;
    const delays = [], retries = [];
    await page.route('https://preview.tinggaljalan.com/**', async route => {
        if (route.request().resourceType() === 'script') {
            moduleRequests++;
            await route.fulfill(moduleRequests === 1
                ? { status: 429, contentType: 'text/javascript', headers: { 'Retry-After': '0' }, body: '// limited' }
                : { contentType: 'text/javascript', body: "document.querySelector('.server-seo-content').remove(); document.querySelector('#app').innerHTML = '<h1>News ready</h1>';" });
        } else {
            documentRequests++;
            await route.fulfill({ contentType: 'text/html', body: '<main class="server-seo-content"><h1>Server news</h1></main><div id="app"></div><script type="module" src="/build/module.js"></script>' });
        }
    });
    await navigateHostedPreview(page, 'https://preview.tinggaljalan.com/news', {
        ready: () => waitForHostedHandoff(page, 3000),
        wait: async ms => delays.push(ms), onRetry: retry => retries.push(retry),
    });
    expect(documentRequests).toBe(2);
    expect(moduleRequests).toBe(2);
    expect(delays).toEqual([10000]);
    expect(retries).toEqual([{ attempt: 1, status: 429, delaySeconds: 10 }]);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('News ready');
    await expect(page.locator('.server-seo-content')).toHaveCount(0);
});

test('hosted parity can wait for the rendered heading even with an unrelated request pending', async ({ page }) => {
    let finishRequest;
    const pending = new Promise(resolve => { finishRequest = resolve; });
    let requestStarted;
    const started = new Promise(resolve => { requestStarted = resolve; });
    await page.route('https://parity-test.invalid/**', async route => {
        if (route.request().resourceType() === 'fetch') {
            requestStarted();
            await pending;
            await route.fulfill({ body: 'done' });
        } else {
            await route.fulfill({ contentType: 'text/html', body: `
                <main class="server-seo-content"><h1>News from the server</h1></main>
                <main id="app" hidden><h1>News</h1></main>
                <script>
                    fetch('/background').catch(() => {});
                    window.revealNews = () => {
                        document.querySelector('.server-seo-content').remove();
                        document.querySelector('#app').hidden = false;
                    };
                </script>
            ` });
        }
    });
    await page.goto('https://parity-test.invalid/news', { waitUntil: 'domcontentloaded' });
    await started;
    try {
        // The server document is readable but is not proof of a successful app handoff.
        await expect(waitForHostedHandoff(page, 300)).rejects.toThrow();
        await page.evaluate(() => window.revealNews());
        await waitForHostedHandoff(page);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('News');
        await expect(page.waitForLoadState('networkidle', { timeout: 700 })).rejects.toThrow();
    } finally {
        finishRequest();
    }
});
