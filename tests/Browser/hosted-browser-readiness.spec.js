import { test, expect } from '@playwright/test';
import { waitForHostedHandoff } from '../../scripts/testing/hosted-browser-readiness.mjs';

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
