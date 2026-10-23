import { expect, test } from '@playwright/test';
import { articlePath } from '../../resources/js/utils/article-url.js';

test('article URL encoding preserves the raw slug and encodes each segment once', () => {
  expect(articlePath('Legacy Bromo 100%20 & Friends')).toBe('/news/Legacy%20Bromo%20100%2520%20%26%20Friends');
  expect(articlePath("Café (Bromo)! 'Guide'")).toBe('/news/Caf%C3%A9%20%28Bromo%29%21%20%27Guide%27');
  expect(articlePath('Bromo-wildfire')).toBe('/news/Bromo-wildfire');
});

for (const catalog of ['routes', 'news']) {
  for (const [query, canonicalQuery, robots] of [
    ['?page=1', '', 'index,follow'],
    ['?page=2&utm_source=test', '?page=2', 'index,follow'],
    ['?page=2&search=bromo', '', 'noindex,follow'],
    ['?page=2&lang=id', '?page=2', 'noindex,follow'],
  ]) {
    test(`${catalog}${query} preserves canonical and robots after hydration`, async ({ page, baseURL }) => {
      await page.goto(`/${catalog}${query}`);
      await expect(page.locator('.server-seo-content')).toHaveCount(0);
      await expect(page.locator('link[rel="canonical"]')).toHaveCount(1);
      await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', `${baseURL}/${catalog}${canonicalQuery}`);
      await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', robots);
    });
  }
}

test('legacy article card navigation and direct reload keep the encoded URL and content', async ({ page, request, baseURL }) => {
  const path = '/news/Legacy%20Bromo%20100%2520%20%26%20Friends';
  const initial = await request.get('/news');
  expect(await initial.text()).toContain(`href="${path}"`);
  await page.goto('/news');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  // The latest panel and catalog both show this story; use its catalog card.
  const card = page.getByRole('link').filter({ has: page.getByRole('heading', { name: 'Legacy URL compatibility story', exact: true }) }).last();
  await card.press('Enter');
  await expect(page).toHaveURL(`${baseURL}${path}`);
  await expect(page.getByRole('heading', { name: 'Legacy URL compatibility story', exact: true, level: 1 })).toBeVisible();
  await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', `${baseURL}${path}`);
  await page.reload();
  await expect(page.getByRole('heading', { name: 'Legacy URL compatibility story', exact: true, level: 1 })).toBeVisible();
  await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', `${baseURL}${path}`);
});
