import { chromium, errors, request } from '@playwright/test';
import { waitForHostedHandoff } from './hosted-browser-readiness.mjs';
import { attachStartupDiagnostics } from './preview-startup-diagnostics.mjs';
import { navigateHostedPreview } from './hosted-rate-limit.mjs';

const base = 'https://preview.tinggaljalan.com';
const password = process.env.STAGING_REVIEW_PASSWORD;
const targetPage = process.env.PREVIEW_PROBE_PAGE;
const result = { stage: 'configuration' };
let browser;
let startupDiagnostics;
try {
    if (!password || !['routes', 'news'].includes(targetPage)) throw new Error('Invalid review configuration');
    const route = '/' + targetPage;
    const credentials = { username: 'reviewer', password, origin: base };
    result.page = targetPage;

    // Direct requests do not follow redirects or send credentials to another origin.
    result.stage = 'runner-http';
    for (const mode of ['always', 'unauthorized']) {
        const client = await request.newContext({ httpCredentials: { ...credentials, send: mode } });
        try {
            const response = await client.get(base + route, { maxRedirects: 0, timeout: 25_000 });
            result[mode === 'always' ? 'preemptiveStatus' : 'challengeStatus'] = response.status();
        } finally {
            await client.dispose();
        }
    }

    result.stage = 'launch';
    browser = await chromium.launch();
    const context = await browser.newContext({
        httpCredentials: credentials,
    });
    const page = await context.newPage();
    startupDiagnostics = attachStartupDiagnostics(page);
    result.stage = 'navigation';
    const response = await navigateHostedPreview(page, base + route, { onRetry: retry => {
        result.rateLimitRetries = [...(result.rateLimitRetries ?? []), retry];
    } });
    result.status = response?.status() ?? 0;
    result.redirected = !!response?.request().redirectedFrom();
    result.expectedPage = new URL(page.url()).origin === base && new URL(page.url()).pathname === route;
    if (!response?.ok() || !result.expectedPage) {
        throw new Error('Unexpected response');
    }
    result.stage = 'handoff';
    const [handoff, idle] = await Promise.allSettled([
        waitForHostedHandoff(page),
        page.waitForLoadState('networkidle', { timeout: 30_000 }),
    ]);
    const category = status => status.status === 'fulfilled' ? 'passed' : status.reason instanceof errors.TimeoutError ? 'timeout' : 'error';
    result.handoff = category(handoff);
    result.networkIdle = category(idle);
    result.startup = startupDiagnostics();
    result.document = await page.evaluate(() => {
        let payload;
        try { payload = JSON.parse(document.querySelector('script[data-page="app"]')?.textContent ?? 'null'); } catch { /* Report shape only. */ }
        return {
            serverFallback: !!document.querySelector('.server-seo-content'),
            initialStage: !!document.querySelector('[data-initial-stage]'),
            appChildren: document.querySelector('#app')?.childElementCount ?? 0,
            initialPage: !!payload,
            translations: !!payload?.props?.translations?.searchTitle,
        };
    });
    if (handoff.status !== 'fulfilled') process.exitCode = 1;
} catch (error) {
    result.failure = error instanceof errors.TimeoutError ? 'timeout' : 'error';
    process.exitCode = 1;
} finally {
    if (startupDiagnostics) result.startup = startupDiagnostics();
    console.log(JSON.stringify(result));
    await browser?.close();
}
