import { expect, test } from '@playwright/test';

const brandedTitle = /^(?:Tinggal Jalan)(?:\s*\||$)|\|\s*Tinggal Jalan$/i;

test.describe('rendered SEO metadata', () => {
  for (const path of ['/', '/routes', '/news', '/booking']) {
    test(`${path} has one brand occurrence after hydration`, async ({ page }) => {
      await page.goto(path);

      await expect(page.locator('.server-seo-content')).toHaveCount(0);

      await expect.poll(async () => {
        const title = await page.title();

        return {
          hasValidBrandPosition: brandedTitle.test(title),
          brandCount: (title.match(/Tinggal Jalan/gi) ?? []).length,
        };
      }).toEqual({ hasValidBrandPosition: true, brandCount: 1 });
    });
  }

  test('footer exposes the configured business address', async ({ page }) => {
    await page.goto('/');

    const address = page.locator('footer address');

    await expect(address).toBeVisible();
    await expect(address).toContainText('Jalan Danau Tondano Dalam A2 D28 Sawojajar, Kota Malang');
    await expect(address.locator('..')).toHaveAttribute('href', /google\.com\/maps/);
  });
});
