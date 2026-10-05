import { chromium } from '@playwright/test';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const [configFile, revision, output] = process.argv.slice(2);
if (!configFile || !/^[a-f0-9]{40}$/.test(revision ?? '') || !output) {
    throw new Error('Usage: staging-browser-smoke.mjs <private-config> <sha> <output>');
}
const config = JSON.parse(await readFile(configFile, 'utf8'));
const base = 'https://preview.tinggaljalan.com';
await mkdir(output, { recursive: true, mode: 0o700 });
const browser = await chromium.launch();
const results = [];
try {
    const anonymous = await browser.newContext();
    for (const route of ['/', '/up', '/admin/login']) {
        const response = await anonymous.request.get(`${base}${route}`);
        if (response.status() !== 401) throw new Error('Unauthenticated preview access was not rejected.');
    }
    await anonymous.close();
    for (const [name, viewport, isMobile] of [
        ['desktop', { width: 1440, height: 1000 }, false],
        ['mobile', { width: 390, height: 844 }, true],
    ]) {
        const context = await browser.newContext({
            viewport, isMobile, deviceScaleFactor: 1,
            httpCredentials: { username: 'reviewer', password: config.review_password, origin: base },
        });
        const runtime = await context.request.get(`${base}/up?revision=${revision}`);
        const health = await runtime.json();
        if (!runtime.ok() || health.status !== 'up' || health.revision !== revision) {
            throw new Error('The browser received an unexpected runtime revision.');
        }
        const page = await context.newPage();
        const errors = [];
        const failedAssets = [];
        page.on('pageerror', () => errors.push('Application JavaScript error'));
        page.on('response', response => {
            if (response.url().startsWith(`${base}/build/`) && response.status() >= 400) failedAssets.push('Built asset failed');
        });
        for (const [label, route] of [['home', '/'], ['routes', '/routes'], ['news', '/news'], ['admin-login', '/admin/login']]) {
            const response = await page.goto(`${base}${route}`, { waitUntil: 'networkidle' });
            if (!response?.ok()) throw new Error('A preview page failed to render.');
            if (!/noindex/i.test(response.headers()['x-robots-tag'] ?? '')) throw new Error('Preview noindex header is missing.');
            await page.locator('body').waitFor();
            if (await page.locator('body').innerText() === '') throw new Error('The preview rendered an empty body.');
            if (await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 2)) {
                throw new Error('Preview layout overflows the viewport.');
            }
            await page.screenshot({ path: path.join(output, `${name}-${label}.png`), fullPage: true });
            results.push(`${name}: ${label} rendered, authenticated, noindex`);
            if (label === 'routes' || label === 'news') {
                const detail = label === 'news'
                    ? page.locator('article[role="link"]').first()
                    : page.locator(`a[href^="/${label}/"]`).first();
                if (await detail.count() === 0) throw new Error('Curated preview detail link is missing.');
                let target;
                if (label === 'news') {
                    await Promise.all([
                        page.waitForURL(new RegExp('^https://preview[.]tinggaljalan[.]com/news/[^/?]+')),
                        detail.click(),
                    ]);
                    await page.waitForLoadState('networkidle');
                    target = page.url();
                } else {
                    const href = await detail.getAttribute('href');
                    if (!href?.startsWith(`/${label}/`)) throw new Error('Unexpected preview detail target.');
                    target = `${base}${href}`;
                    await page.goto(target, { waitUntil: 'networkidle' });
                }
                const detailResponse = await context.request.get(target);
                if (!detailResponse?.ok()) throw new Error('Preview detail page failed.');
                await page.screenshot({ path: path.join(output, `${name}-${label}-detail.png`), fullPage: true });
                results.push(`${name}: ${label} detail rendered`);
            }
        }
        if (errors.length || failedAssets.length) throw new Error('Preview JavaScript or asset errors were detected.');
        await context.close();
    }
    await writeFile(path.join(output, 'verification.txt'), `Revision: ${revision}\n${results.join('\n')}\n`, { mode: 0o600 });
} finally {
    await browser.close();
}
