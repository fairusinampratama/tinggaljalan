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
const diagnostics = [];
const publicRoutes = new Set(['/', '/routes', '/news', '/about-us', '/privacy-policy', '/?lang=id', '/news?lang=cn', '/routes?destination=bromo']);
const redact = value => Object.values(config).filter(value => typeof value === 'string' && value.length).reduce(
    (text, secret) => text.replaceAll(secret, '[redacted]'), String(value),
);
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
        page.on('pageerror', error => errors.push({ route: new URL(page.url()).pathname, message: redact(error.message) }));
        page.on('response', response => {
            const url = new URL(response.url());
            if (url.origin === base && /\.(js|css)$/.test(url.pathname) && response.status() >= 400) {
                failedAssets.push({ path: url.pathname, status: response.status() });
            }
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
                publicRoutes.add(new URL(target).pathname);
                await page.screenshot({ path: path.join(output, `${name}-${label}-detail.png`), fullPage: true });
                results.push(`${name}: ${label} detail rendered`);
            }
        }
        diagnostics.push({ viewport: name, errors, failedAssets });
        await context.close();
    }
    const credentials = { username: 'reviewer', password: config.review_password, origin: base };
    const initialContext = await browser.newContext({ javaScriptEnabled: false, httpCredentials: credentials, viewport: { width: 1440, height: 1000 } });
    const visitorContext = await browser.newContext({ httpCredentials: credentials, viewport: { width: 1440, height: 1000 } });
    const initial = await initialContext.newPage();
    const visitor = await visitorContext.newPage();
    let articleRoute;
    for (const [index, route] of [...publicRoutes].entries()) {
        const target = `${base}${route}`;
        const normal = await initialContext.request.get(target);
        const bot = await initialContext.request.get(target, { headers: { 'User-Agent': 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' } });
        if (!normal.ok() || !bot.ok()) throw new Error('Initial public HTML request failed.');
        const raw = await normal.text();
        const botRaw = await bot.text();
        const fallback = html => html.match(/<main\b[^>]*class="[^"]*server-seo-content[^"]*"[^>]*>[\s\S]*?<\/main>/)?.[0];
        if (!fallback(raw) || fallback(raw) !== fallback(botRaw)) throw new Error(`Crawler fallback parity failed for ${route}.`);
        await writeFile(path.join(output, `initial-${index}.html`), raw, { mode: 0o600 });
        await initial.goto(target, { waitUntil: 'networkidle' });
        if (!await initial.locator('.server-seo-content').isVisible() || await initial.locator('h1').count() !== 1) throw new Error(`No-JavaScript content failed for ${route}.`);
        await initial.screenshot({ path: path.join(output, `no-js-${index}.png`), fullPage: true });
        const expected = await initial.evaluate(() => {
            const page = JSON.parse(document.querySelector('script[data-page="app"]').textContent);
            return { page, h1: document.querySelector('h1').textContent, sections: [...document.querySelectorAll('.server-seo-content > section[id]')].map(section => ({
                id: section.id, heading: section.querySelector(':scope > h2')?.textContent,
                paragraphs: [...section.querySelectorAll(':scope > p')].map(p => ({ text: p.textContent, breaks: p.querySelectorAll('br').length })),
            })) };
        });
        await visitor.goto(target, { waitUntil: 'networkidle' });
        await visitor.locator('.server-seo-content').waitFor({ state: 'detached' });
        const normalize = text => text.replace(/\s+/g, ' ').trim();
        if (await visitor.locator('h1').count() !== 1 || normalize(await visitor.locator('h1').textContent()) !== normalize(expected.h1)) throw new Error(`Visitor H1 parity failed for ${route}.`);
        if (expected.page.component === 'NewsDetailPage') {
            articleRoute = route;
            const language = expected.page.props.language || 'us';
            const localized = value => typeof value === 'string' ? value : [language, 'us', 'en', 'id', 'cn'].map(key => value?.[key]).find(text => typeof text === 'string' && text.trim()) || '';
            const cms = expected.page.props.article.sections || [];
            if (cms.length !== expected.sections.length) throw new Error('Initial article section count differs from CMS.');
            for (const [i, section] of cms.entries()) {
                const actual = expected.sections[i];
                const heading = localized(section.heading).trim();
                const body = localized(section.body).replace(/\r\n?/g, '\n').trim();
                const paragraphs = body.split(/\n[\t ]*\n+/).filter(Boolean).map(text => ({ text: text.replaceAll('\n', ''), breaks: text.split('\n').length - 1 }));
                if (actual.heading?.trim() !== heading || actual.id !== heading.toLowerCase().replace(/[^a-z0-9]+/g, '-') || JSON.stringify(actual.paragraphs) !== JSON.stringify(paragraphs)) throw new Error('Initial article structure/text differs from CMS.');
                const rendered = await visitor.locator(`article section[id="${actual.id}"]`).evaluate(element => ({
                    heading: element.querySelector('h2').textContent,
                    paragraphs: [...element.querySelectorAll(':scope > p')].map(p => ({ text: p.textContent, breaks: p.querySelectorAll('br').length })),
                }));
                if (rendered.heading !== actual.heading || JSON.stringify(rendered.paragraphs) !== JSON.stringify(actual.paragraphs)) throw new Error('Article visitor/server semantic parity failed.');
                const anchor = visitor.locator(`a[href="#${actual.id}"]`);
                if (await anchor.count() !== 1) throw new Error('Article fragment navigation is missing.');
            }
        }
        results.push(`semantic: ${route} initial HTML, Googlebot parity, no-JS, visitor H1 and no duplicate fallback passed`);
    }
    if (!articleRoute) throw new Error('No deployed article was available for semantic verification.');
    for (const [name, viewport] of [['desktop', { width: 1440, height: 1000 }], ['mobile', { width: 390, height: 844 }]]) {
        const failedContext = await browser.newContext({ httpCredentials: credentials, viewport });
        await failedContext.route('**/build/assets/*.js', route => route.abort());
        const failedPage = await failedContext.newPage();
        await failedPage.goto(`${base}${articleRoute}`, { waitUntil: 'networkidle' });
        if (!await failedPage.locator('.server-seo-content').isVisible() || await failedPage.locator('h1').count() !== 1) throw new Error('Blocked JavaScript erased public article content.');
        await failedPage.screenshot({ path: path.join(output, `${name}-blocked-js.png`), fullPage: true });
        results.push(`${name}: blocked frontend retains readable article content`);
        await failedContext.close();
    }
    await initialContext.close();
    await visitorContext.close();
    await writeFile(path.join(output, 'verification.txt'), `Revision: ${revision}\n${results.join('\n')}\n`, { mode: 0o600 });
    await writeFile(path.join(output, 'diagnostics.json'), JSON.stringify(diagnostics, null, 2), { mode: 0o600 });
    if (diagnostics.some(result => result.errors.length || result.failedAssets.length)) {
        console.error(JSON.stringify(diagnostics));
        throw new Error('Preview JavaScript or asset errors were detected; see diagnostics.json.');
    }
} finally {
    await browser.close();
}
