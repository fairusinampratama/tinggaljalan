import { chromium, expect } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

const [revision, output] = process.argv.slice(2);
if (!/^[a-f0-9]{40}$/.test(revision ?? '') || !output) throw new Error('Invalid review arguments');
const base = 'https://tinggaljalan.com';
await mkdir(output, { recursive: true });
const browser = await chromium.launch();
const report = { revision, readOnly: true, viewports: [] };
const localized = value => typeof value === 'string' ? value : value?.us || value?.id || value?.cn || '';
try {
  for (const [name, viewport] of [['desktop', { width: 1440, height: 1000 }], ['tablet', { width: 1024, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
    const context = await browser.newContext({ viewport, isMobile: name === 'mobile', hasTouch: name === 'mobile' });
    const health = await context.request.get(`${base}/up?review_revision=${revision}`);
    expect((await health.json()).revision).toBe(revision);
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const response = await page.goto(`${base}/routes/BROMO`, { waitUntil: 'networkidle' });
    expect(response.status()).toBe(200);
    expect(response.headers()['x-robots-tag'] ?? '').not.toMatch(/noindex/i);
    await expect(page.locator('.server-seo-content')).toHaveCount(0);
    await expect(page.locator('h1')).toHaveCount(1);
    const route = await page.evaluate(() => JSON.parse(document.querySelector('script[data-page="app"]').textContent).props.route);
    const timeline = page.getByTestId('itinerary-timeline');
    await expect(timeline.locator('li')).toHaveCount(route.itinerarySteps.length);
    for (const [index, item] of route.itinerarySteps.entries()) {
      const step = timeline.locator('li').nth(index);
      await expect(step).toContainText(localized(item.title));
      if (item.timeLabel) await expect(step).toContainText(item.timeLabel);
      if (localized(item.description)) await expect(step).toContainText(localized(item.description));
    }
    const consent = page.getByTestId('consent-decline');
    if (await consent.isVisible()) await consent.click();
    const cards = await page.locator('[data-route-content-card]').evaluateAll(els => els.map(el => {
      const r = el.getBoundingClientRect(), s = getComputedStyle(el);
      return { heading: el.querySelector('h2,h3')?.textContent.trim(), x: r.x, y: r.y + scrollY, width: r.width, height: r.height,
        style: [s.backgroundColor, s.borderRadius, s.borderTopColor, s.borderTopWidth, s.boxShadow, s.paddingTop, s.paddingRight] };
    }));
    expect(cards.length).toBeGreaterThan(6);
    for (const card of cards) expect(card.style).toEqual(cards[0].style);
    await page.evaluate(() => scrollTo({ top: 0, behavior: 'instant' }));
    await page.screenshot({ path: path.join(output, `${name}-complete-page.png`), fullPage: true });
    for (const [index, fraction] of [0, .33, .67, 1].entries()) {
      await page.evaluate(f => scrollTo({ top: f * (document.documentElement.scrollHeight - innerHeight), behavior: 'instant' }), fraction);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
      await page.screenshot({ path: path.join(output, `${name}-scroll-${index}.png`) });
    }
    const gallery = page.getByRole('button', { name: 'Open full-screen gallery', exact: true });
    await gallery.scrollIntoViewIfNeeded();
    const scrollBefore = await page.evaluate(() => scrollY);
    const total = route.gallery.length;
    if (total > 1) {
      const selected = page.getByRole('button', { name: `View photo ${total}`, exact: true }).filter({ visible: true });
      await selected.evaluate(el => el.click());
      await expect(page.locator('#route-detail')).toContainText(`${total} / ${total}`);
      expect(await page.evaluate(() => scrollY)).toBe(scrollBefore);
      expect(await selected.evaluate(el => { const a = el.getBoundingClientRect(), b = el.parentElement.getBoundingClientRect(); return a.top >= b.top - 2 && a.bottom <= b.bottom + 2 && a.left >= b.left - 2 && a.right <= b.right + 2; })).toBe(true);
    }
    await expect.poll(() => gallery.locator('img').evaluate(img => img.complete && img.naturalWidth > 0), { timeout: 30000 }).toBe(true);
    await page.screenshot({ path: path.join(output, `${name}-gallery.png`) });
    await gallery.click();
    const dialog = page.getByRole('dialog', { name: 'Trip photo gallery' });
    await expect(dialog.getByRole('button', { name: 'Close gallery' })).toBeFocused();
    if (total > 1) {
      await page.keyboard.press('ArrowRight');
      await expect(dialog).toContainText(`1 / ${total}`);
      if (name === 'mobile') {
        await dialog.evaluate(el => {
          for (const [type, x] of [['touchstart', 250], ['touchend', 100]]) {
            const event = new Event(type, { bubbles: true });
            Object.defineProperty(event, 'changedTouches', { value: [{ clientX: x }] });
            el.querySelector('div').dispatchEvent(event);
          }
        });
        await expect(dialog).toContainText(`2 / ${total}`);
      }
    }
    await expect.poll(() => dialog.locator('img').first().evaluate(img => img.complete && img.naturalWidth > 0), { timeout: 30000 }).toBe(true);
    await page.screenshot({ path: path.join(output, `${name}-fullscreen.png`) });
    await page.keyboard.press('Escape');
    await expect(dialog).toHaveCount(0);
    expect(errors).toEqual([]);
    const metadata = await page.evaluate(() => ({ canonical: document.querySelector('link[rel="canonical"]')?.href, ogImage: document.querySelector('meta[property="og:image"]')?.content, logo: document.querySelector('nav img')?.getAttribute('src') }));
    expect(metadata.canonical).toBe(`${base}/routes/BROMO`);
    report.viewports.push({ name, viewport, cards, metadata, itineraryCount: route.itinerarySteps.length, galleryCount: total, galleryAndLoadedFullscreen: true, noOverflowOrDocumentJump: true });
    await context.close();
  }
  const initialContext = await browser.newContext({ javaScriptEnabled: false });
  const initial = await initialContext.newPage();
  await initial.goto(`${base}/routes/BROMO`, { waitUntil: 'networkidle' });
  await expect(initial.getByTestId('server-itinerary-timeline').locator('li')).toHaveCount(report.viewports[0].itineraryCount);
  await expect(initial.locator('h1')).toHaveCount(1);
  await initial.screenshot({ path: path.join(output, 'initial-html.png'), fullPage: true });
  await initialContext.close();
  await writeFile(path.join(output, 'verification.json'), JSON.stringify(report, null, 2));
  console.log('Production desktop/tablet/mobile cards, itinerary, gallery, loaded fullscreen and initial HTML passed; no CMS or booking writes.');
} finally { await browser.close(); }
