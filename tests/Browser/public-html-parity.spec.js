import { expect, test } from '@playwright/test';

const paths = ['/', '/routes', '/news', '/routes/bromo-sunrise', '/news/paket-wisata-bromo-dari-malang', '/about-us', '/privacy-policy'];
const normalize = (text) => text.replace(/\s+/g, ' ').trim();

for (const path of paths) {
  test(`${path} exposes real headings before JS and removes fallback after mount`, async ({ browser, page }) => {
    const context = await browser.newContext({ javaScriptEnabled: false });
    const server = await context.newPage();
    await server.goto(path);
    const fallback = server.locator('.server-seo-content');
    await expect(fallback).toBeVisible();
    const headings = await fallback.locator('h1,h2,h3').allTextContents();
    await page.goto(path);
    await expect(page.locator('.server-seo-content')).toHaveCount(0);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect.poll(() => page.locator('h1').textContent()).toMatch(new RegExp(normalize(headings[0]).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
    for (const heading of headings) {
      await expect.poll(async () => (await page.locator('h1,h2,h3').allTextContents()).map(normalize)).toContain(normalize(heading));
    }
    expect(await server.locator('meta[name="robots"]').getAttribute('content')).toBe(await page.locator('meta[name="robots"]').getAttribute('content'));
    expect(await server.locator('link[rel="canonical"]').getAttribute('href')).toBe(await page.locator('link[rel="canonical"]').getAttribute('href'));
    await context.close();
  });
}

test('article paragraphs, section order and fragment links match initial HTML', async ({ browser, page }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const server = await context.newPage();
  const path = '/news/paket-wisata-bromo-dari-malang';
  await server.goto(path);
  const expected = await server.locator('.server-seo-content section[id]').evaluateAll((sections) => sections.map((section) => ({
    id: section.id,
    heading: section.querySelector('h2').textContent,
    paragraphs: [...section.querySelectorAll(':scope > p')].map((p) => p.textContent),
  })));
  await page.goto(path);
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  for (const section of expected) {
    const actual = page.locator(`article section[id="${section.id}"]`);
    await expect(actual.locator('h2')).toHaveText(section.heading);
    expect(await actual.locator(':scope > p').allTextContents()).toEqual(section.paragraphs);
    const link = page.locator(`a[href="#${section.id}"]`);
    await expect(link).toHaveCount(1);
    if (await link.isVisible()) { await link.click(); await expect(actual).toBeInViewport(); }
  }
  await context.close();
});

test('blocked frontend scripts leave readable public content', async ({ page }) => {
  await page.route('**/build/assets/*.js', (route) => route.abort());
  await page.goto('/news/paket-wisata-bromo-dari-malang');
  await expect(page.locator('.server-seo-content')).toBeVisible();
  await expect(page.locator('h1')).toHaveCount(1);
  await expect(page.locator('.server-seo-content section[id]').first()).toBeVisible();
});

test('language and filtered variants remain readable and noindex without JS', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  for (const path of ['/?lang=id', '/news?lang=cn', '/routes?destination=bromo', '/news?search=missing']) {
    await page.goto(path);
    await expect(page.locator('.server-seo-content')).toBeVisible();
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'noindex,follow');
  }
  await context.close();
});

test('CMS multiline text becomes escaped paragraphs and line breaks in React', async ({ page }) => {
  const text = 'First paragraph\r\nsecond line\r\n\r\nSecond paragraph <script>alert(1)</script> & text';
  await page.route('**/news/paket-wisata-bromo-dari-malang', async (route) => {
    const response = await route.fetch();
    let html = await response.text();
    const pattern = /(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/;
    const payload = JSON.parse(html.match(pattern)[2]);
    payload.props.article.sections[0].body.us = text;
    html = html.replace(pattern, (_, start, data, end) => start + JSON.stringify(payload).replace(/</g, '\\u003c') + end);
    await route.fulfill({ response, body: html });
  });
  await page.goto('/news/paket-wisata-bromo-dari-malang');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  const section = page.locator('article section[id]').first();
  await expect(section.locator(':scope > p')).toHaveCount(2);
  await expect(section.locator('p').first()).toHaveText('First paragraphsecond line');
  await expect(section.locator('p').last()).toHaveText('Second paragraph <script>alert(1)</script> & text');
  await expect(section.locator('p br')).toHaveCount(1);
  await expect(section.locator('script')).toHaveCount(0);
});
