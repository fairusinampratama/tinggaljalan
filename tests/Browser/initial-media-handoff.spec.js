import { expect, test } from '@playwright/test';

const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900"><rect width="1600" height="900" fill="#2f6f6d"/></svg>';

for (const fail of [false, true]) {
  test(`initial view survives ${fail ? 'failed' : 'delayed'} replacement media`, async ({ page }, testInfo) => {
    let release;
    const gate = new Promise(resolve => { release = resolve; });
    await page.route('**/', async route => {
      const response = await route.fetch();
      const html = (await response.text()).replace(/(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/,
        (_, start, json, end) => {
          const payload = JSON.parse(json);
          payload.props.publicData.site.logoUrl = '/test-react-logo.svg';
          const first = payload.props.publicData.home.heroSlides[0];
          first.desktopImage = '/test-react-hero.svg';
          first.mobileImage = '/test-react-hero.svg';
          return start + JSON.stringify(payload).replace(/</g, '\\u003c') + end;
        });
      await route.fulfill({ response, body: html });
    });
    await page.route('**/test-react-*.svg', async route => {
      await gate;
      if (fail) await route.abort();
      else await route.fulfill({ contentType: 'image/svg+xml', body: svg, headers: { 'cache-control': 'no-store' } });
    });
    try {
      await page.goto('/', { waitUntil: 'commit' });
      await expect(page.locator('[data-initial-stage]')).toHaveCount(1);
      await expect(page.locator('.server-seo-content')).toBeVisible();
      await expect(page.locator('#home')).toHaveCount(1);
      await expect(page.locator('[data-initial-stage]')).toHaveAttribute('aria-hidden', 'true');
      await page.screenshot({ path: testInfo.outputPath('media-pending.png') });
      release();
      await expect(page.locator('.server-seo-content')).toHaveCount(0);
      await expect(page.locator('[data-initial-stage]')).toHaveCount(0);
      await expect(page.locator('#home')).toContainText('Find a Trip');
      await expect(page.locator('#destination').getByRole('link').first()).toBeVisible();
      if (!fail) {
        expect(await page.locator('nav img, #home img').evaluateAll(images => images.slice(0, 2).every(img => img.complete && img.naturalWidth > 0))).toBe(true);
      }
      await page.screenshot({ path: testInfo.outputPath('media-ready.png') });
    } finally { release(); }
  });
}
