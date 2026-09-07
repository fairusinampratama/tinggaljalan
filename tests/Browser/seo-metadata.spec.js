import { expect, test } from '@playwright/test';

const brandedTitle = /^(?:Tinggal Jalan)(?:\s*\||$)|\|\s*Tinggal Jalan$/i;

test.describe('rendered SEO metadata', () => {
  for (const path of [
    '/',
    '/routes',
    '/routes/bromo-sunrise',
    '/news',
    '/news/paket-wisata-bromo-dari-malang',
    '/booking',
  ]) {
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
    const addressLink = address.locator('../..');

    await expect(address).toHaveCount(1);
    await expect(address).toBeVisible();
    await expect(address).toContainText('Jalan Danau Tondano Dalam A2 D28 Sawojajar, Kota Malang');
    await expect(addressLink).toHaveAttribute('href', /google\.com\/maps/);
    await expect(addressLink).toHaveAttribute('target', '_blank');
    await expect(addressLink).toHaveAttribute('rel', 'noreferrer');
  });
});
