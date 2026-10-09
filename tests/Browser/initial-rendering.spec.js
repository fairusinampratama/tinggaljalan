import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';

// Optional local cache of the unchanged, publicly hosted seed logo.
// CI and ordinary browser tests continue to use its original URL.
test.beforeEach(async ({ page }) => {
  if (process.env.TINGGALJALAN_TEST_LOGO_FIXTURE) {
    await page.route('https://assets.zyrosite.com/**', (route) => route.fulfill({
      path: process.env.TINGGALJALAN_TEST_LOGO_FIXTURE, contentType: 'image/png',
    }));
  }
});

// Load the rendered faces explicitly before measuring geometry. The lazy
// page module stays held until after the initial screenshot.
async function settleVisibleFonts(page) {
  await page.evaluate(async () => {
    const fonts = new Set([...document.querySelectorAll('.server-seo-content h2, .server-seo-content h3, .server-seo-content p, .server-seo-content label, .server-seo-content select, .server-seo-content a')].map((element) => {
      const style = getComputedStyle(element);
      return `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
    }));
    await Promise.all([...fonts].map((font) => document.fonts.load(font)));
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
  });
}

// Hold the lazy homepage module: bootstrap and document loading can finish,
// but React cannot replace the server document. This reproduces the actual
// asynchronous page-resolution transition across browser engines.
for (const language of ['us', 'id', 'cn']) {
  test(`initial ${language} homepage keeps its hero geometry and complete copy`, async ({ page }, testInfo) => {
    const browserErrors = [];
    page.on('pageerror', (error) => browserErrors.push(error.message));
    let release;
    const gate = new Promise((resolve) => { release = resolve; });
    await page.route('**/build/assets/HomePage-*.js', async (route) => { await gate; await route.continue(); });
    await page.addInitScript(() => {
      window.__initialRender = { incomplete: false, ready: null };
      new MutationObserver(() => {
        if (!document.querySelector('.server-seo-content') && document.querySelector('#home')) {
          const text = document.querySelector('#home').textContent;
          if (!text.includes(window.__expectedSearchTitle)) window.__initialRender.incomplete = true;
          window.__initialRender.ready ??= performance.now();
        }
      }).observe(document, { childList: true, subtree: true });
    });
    try {
      await page.goto(`/?lang=${language}`, { waitUntil: 'commit' });
      await expect(page.locator('.server-seo-content')).toBeVisible();
      await settleVisibleFonts(page);
      const initial = await page.evaluate(() => { const box = document.querySelector('.server-seo-content #home')?.getBoundingClientRect(); return box ? { height: box.height } : null; });
      const copy = JSON.parse(readFileSync(new URL(`../../resources/js/data/translations/${language}.json`, import.meta.url))).searchTitle;
      await page.evaluate((title) => { window.__expectedSearchTitle = title; }, copy);
      await page.screenshot({ path: testInfo.outputPath('initial.png') });
      release();
      await expect(page.locator('.server-seo-content')).toHaveCount(0);
      await expect(page.locator('#home')).toContainText(copy || 'Find a Trip');
      await page.screenshot({ path: testInfo.outputPath('react.png') });
      expect(initial, 'server hero must use the existing layout').not.toBeNull();
      const mounted = await page.locator('#home').boundingBox();
      expect(Math.abs(initial.height - mounted.height)).toBeLessThan(8);
      expect((await page.evaluate(() => window.__initialRender)).incomplete).toBe(false);
      expect(browserErrors).toEqual([]);
    } finally { release(); }
  });
}

test('destination fragment exists before JS and stays aligned after mount', async ({ page }, testInfo) => {
  let release;
  const gate = new Promise((resolve) => { release = resolve; });
  await page.route('**/build/assets/HomePage-*.js', async (route) => { await gate; await route.continue(); });
  try {
    await page.goto('/#destination', { waitUntil: 'commit' });
    await expect(page.locator('.server-seo-content')).toBeVisible();
    await expect(page.locator('#destination')).toHaveCount(1);
    await expect(page.locator('#destination')).toBeInViewport();
    await settleVisibleFonts(page);
    await page.screenshot({ path: testInfo.outputPath('fragment-initial.png') });
    const before = await page.locator('#destination').boundingBox();
    release();
    await expect(page.locator('.server-seo-content')).toHaveCount(0);
    await expect(page.locator('#destination')).toBeInViewport();
    await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    const after = await page.locator('#destination').boundingBox();
    expect(Math.abs(before.y - after.y)).toBeLessThan(8);
    await page.screenshot({ path: testInfo.outputPath('fragment-react.png') });
  } finally { release(); }
});

test('failed page module leaves branding, hero and crawlable content usable', async ({ page }) => {
  await page.route('**/build/assets/HomePage-*.js', (route) => route.abort());
  await page.goto('/');
  await expect(page.locator('.server-seo-content')).toBeVisible();
  await expect(page.locator('.server-seo-content nav img')).toHaveAttribute('src', /logo/);
  await expect(page.locator('.server-seo-content #home h2').first()).toBeVisible();
  await expect(page.locator('.server-seo-content #destination a[href*="destination="]').first()).toBeVisible();
});

test('initial locale does not depend on a later translation chunk; switches stay complete', async ({ page }) => {
  await page.route(/\/build\/assets\/(?:us|id|cn)-[^/]+\.js$/, (route) => route.abort());
  await page.goto('/');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  await expect(page.locator('#home')).toContainText('Find a Trip');
  for (const [label, title] of [['ID', 'Cari Trip'], ['中文', '寻找行程'], ['EN', 'Find a Trip']]) {
    const button = page.locator('nav').getByRole('button', { name: label, exact: true });
    if (!await button.isVisible()) await page.locator('nav').getByRole('button', { name: 'Open menu' }).click();
    await page.locator('nav').getByRole('button', { name: label, exact: true }).click();
    await expect(page.locator('#home')).toContainText(title);
  }
});

test('missing initial translations never mount an incomplete interface', async ({ page }) => {
  await page.route('**/', async (route) => {
    const response = await route.fetch();
    const html = (await response.text()).replace(/(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/,
      (_, start, json, end) => {
        const payload = JSON.parse(json);
        delete payload.props.translations;
        return start + JSON.stringify(payload).replace(/</g, '\\u003c') + end;
      });
    await route.fulfill({ response, body: html });
  });
  await page.goto('/');
  await expect(page.locator('.server-seo-content')).toBeVisible();
  await expect(page.locator('#home')).toContainText('Find a Trip');
});

test('CSS and JavaScript failures retain all meaningful homepage content', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.route('**/build/assets/*.css', (route) => route.abort());
  await page.goto('/#destination');
  await expect(page.locator('.server-seo-content')).toBeVisible();
  await expect(page.locator('#destination h2')).toBeVisible();
  await expect(page.locator('#home h2')).toHaveCount(2);
  await expect(page.locator('#home form')).toHaveAttribute('action', '/routes');
  await context.close();
});
