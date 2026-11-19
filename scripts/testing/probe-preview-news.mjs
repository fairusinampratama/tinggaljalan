import { chromium, errors } from '@playwright/test';
import { waitForHostedHandoff } from './hosted-browser-readiness.mjs';

const base = 'https://preview.tinggaljalan.com';
const password = process.env.STAGING_REVIEW_PASSWORD;
const result = { stage: 'configuration' };
let browser;
try {
    if (!password) throw new Error('Missing review configuration');
    result.stage = 'launch';
    browser = await chromium.launch();
    const context = await browser.newContext({
        httpCredentials: { username: 'reviewer', password, origin: base },
    });
    const page = await context.newPage();
    result.stage = 'navigation';
    const response = await page.goto(base + '/news', { waitUntil: 'domcontentloaded' });
    result.status = response?.status() ?? 0;
    if (!response?.ok() || new URL(page.url()).origin !== base || new URL(page.url()).pathname !== '/news') {
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
    if (handoff.status !== 'fulfilled') process.exitCode = 1;
} catch (error) {
    result.failure = error instanceof errors.TimeoutError ? 'timeout' : 'error';
    process.exitCode = 1;
} finally {
    console.log(JSON.stringify(result));
    await browser?.close();
}
