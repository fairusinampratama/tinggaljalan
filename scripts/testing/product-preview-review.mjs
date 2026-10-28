import { chromium, expect } from '@playwright/test';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

const [configFile, revision, output] = process.argv.slice(2);
if (!/^[a-f0-9]{40}$/.test(revision ?? '') || !configFile || !output) throw new Error('Invalid review arguments');
const config = JSON.parse(await readFile(configFile, 'utf8'));
const base = 'https://preview.tinggaljalan.com';
const routePath = '/routes/BROMO';
await mkdir(output, { recursive: true });
const browser = await chromium.launch();
const report = { revision, readOnly: true, checks: [] };
const localized = v => typeof v === 'string' ? v : v?.us || v?.id || v?.cn || '';
try {
    for (const [profile, viewport] of [['desktop', { width: 1440, height: 1000 }], ['mobile', { width: 390, height: 844 }]]) {
        const context = await browser.newContext({ viewport, isMobile: profile === 'mobile', hasTouch: profile === 'mobile',
            httpCredentials: { username: 'reviewer', password: config.review_password, origin: base } });
        const health = await context.request.get(`${base}/up?revision=${revision}`);
        expect((await health.json()).revision).toBe(revision);
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', () => errors.push('Page JavaScript error'));
        const response = await page.goto(base + routePath, { waitUntil: 'networkidle' });
        expect(response.ok()).toBe(true);
        expect(response.headers()['x-robots-tag']).toMatch(/noindex/);
        await expect(page.locator('.server-seo-content')).toHaveCount(0);
        const route = await page.evaluate(() => JSON.parse(document.querySelector('script[data-page="app"]').textContent).props.route);
        const steps = route.itinerarySteps;
        expect(steps.length).toBeGreaterThan(0);
        const timeline = page.getByTestId('itinerary-timeline');
        await expect(timeline.locator('li')).toHaveCount(steps.length);
        for (const [i, step] of steps.entries()) {
            await expect(timeline.locator('li').nth(i)).toContainText(localized(step.title));
            if (step.timeLabel) await expect(timeline.locator('li').nth(i)).toContainText(step.timeLabel);
            if (localized(step.description)) await expect(timeline.locator('li').nth(i)).toContainText(localized(step.description));
        }
        const initial = await response.text();
        expect(initial).toContain('server-itinerary-timeline');
        const consent = page.getByTestId('consent-decline');
        if (await consent.isVisible()) await consent.click();
        await page.evaluate(() => scrollTo({ top: 0, behavior: 'instant' }));
        await page.screenshot({ path: path.join(output, `${profile}-actual-bromo-full.png`), fullPage: true });
        await timeline.scrollIntoViewIfNeeded();
        await timeline.screenshot({ path: path.join(output, `${profile}-actual-bromo-timeline.png`) });
        report.checks.push({ profile, actualGalleryCount: route.gallery.length, actualItineraryCount: steps.length, initialHtmlTimeline: true });

        for (const total of [1, 2, 10, 20]) {
            const originalRequests = [];
            const track = request => { if (request.url().includes('?product-review=')) originalRequests.push(request.url()); };
            page.on('request', track);
            await page.route(base + routePath, async interception => {
                const original = await interception.fetch();
                const html = (await original.text()).replace(/(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/, (_, start, json, end) => {
                    const payload = JSON.parse(json);
                    payload.props.route.gallery = Array.from({ length: total }, (_, i) => `${route.gallery[i % route.gallery.length]}?product-review=${i}`);
                    payload.props.route.itinerarySteps = ['23:00', '00:15', '', 'Morning'].map((timeLabel, i) => ({ id: i + 1, dayNumber: i < 3 ? 1 : 2, displayOrder: i, timeLabel,
                        title: { us: `Review activity ${i + 1}` }, description: { us: 'Complete editorial activity description. '.repeat(12) } }));
                    return start + JSON.stringify(payload).replace(/</g, '\\u003c') + end;
                });
                await interception.fulfill({ response: original, body: html });
            });
            await page.goto(base + routePath, { waitUntil: 'networkidle' });
            await expect(page.locator('.server-seo-content')).toHaveCount(0);
            await expect(timeline.locator('li')).toHaveCount(4);
            await expect(timeline).toContainText('Day 2');
            const open = page.getByRole('button', { name: 'Open full-screen gallery', exact: true });
            await open.scrollIntoViewIfNeeded();
            const y = await page.evaluate(() => scrollY);
            if (total > 1) {
                const selected = page.getByRole('button', { name: `View photo ${total}`, exact: true }).filter({ visible: true });
                await selected.evaluate(el => el.click());
                await expect(page.locator('#route-detail')).toContainText(`${total} / ${total}`);
                expect(await page.evaluate(() => scrollY)).toBe(y);
                expect(await selected.evaluate(el => {
                    const a = el.getBoundingClientRect(), b = el.parentElement.getBoundingClientRect();
                    return a.top >= b.top - 2 && a.bottom <= b.bottom + 2 && a.left >= b.left - 2 && a.right <= b.right + 2;
                })).toBe(true);
            }
            await expect.poll(() => open.locator('img').evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
            expect(originalRequests).toHaveLength(0);
            if (total === 20) {
                await page.screenshot({ path: path.join(output, `${profile}-20-photo-gallery.png`) });
                if (profile === 'mobile') await page.getByRole('button', { name: 'View photo 20', exact: true }).filter({ visible: true }).locator('..').screenshot({ path: path.join(output, 'mobile-20-photo-thumbnails.png') });
            }
            await open.click();
            const dialog = page.getByRole('dialog', { name: 'Trip photo gallery' });
            await expect(dialog.getByRole('button', { name: 'Close gallery' })).toBeFocused();
            if (total > 1) {
                await page.keyboard.press('ArrowRight');
                await expect(dialog).toContainText(`1 / ${total}`);
                await dialog.evaluate(el => {
                    for (const [type, x] of [['touchstart', 250], ['touchend', 100]]) {
                        const event = new Event(type, { bubbles: true });
                        Object.defineProperty(event, 'changedTouches', { value: [{ clientX: x }] });
                        el.querySelector('div').dispatchEvent(event);
                    }
                });
                await expect(dialog).toContainText(`2 / ${total}`);
            }
            if (total === 20) await page.screenshot({ path: path.join(output, `${profile}-20-photo-fullscreen.png`) });
            await page.keyboard.press('Escape');
            await expect(dialog).toHaveCount(0);
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
            if (total === 20) {
                await page.evaluate(() => scrollTo({ top: 0, behavior: 'instant' }));
                await page.screenshot({ path: path.join(output, `${profile}-long-multiday-full.png`), fullPage: true });
            }
            await page.unroute(base + routePath);
            page.off('request', track);
            report.checks.push({ profile, fixtureGalleryCount: total, keyboardAndTouch: true, noEarlyOriginalRequests: true, noDocumentJumpOrOverflow: true });
        }
        expect(errors).toHaveLength(0);
        await context.close();
    }
    await writeFile(path.join(output, 'product-verification.json'), JSON.stringify(report, null, 2));
    console.log('Actual deployed Bromo content and 1/2/10/20-photo browser-only fixtures passed on desktop/mobile; no CMS writes.');
} finally { await browser.close(); }
