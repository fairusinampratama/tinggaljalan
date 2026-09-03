import { expect, test } from '@playwright/test';

const brandedTitle = /\| Tinggal Jalan$/;

test.describe('rendered SEO metadata', () => {
  for (const path of ['/', '/routes', '/news', '/booking']) {
    test(`${path} has one brand suffix after hydration`, async ({ page }) => {
      await page.goto(path);

      await expect(page.locator('.server-seo-content')).toHaveCount(0);

      await expect.poll(async () => {
        const title = await page.title();

        return {
          hasBrandSuffix: brandedTitle.test(title),
          brandSuffixCount: (title.match(/\| Tinggal Jalan/g) ?? []).length,
        };
      }).toEqual({ hasBrandSuffix: true, brandSuffixCount: 1 });
    });
  }
});
