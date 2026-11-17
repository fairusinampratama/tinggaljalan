import { expect, test } from './fixtures.js';
import { readFileSync } from 'node:fs';

const prototype = JSON.parse(readFileSync('database/seeders/data/prototype.json', 'utf8'));
const packageFixture = prototype.routeArticles.find(route => route.id === 'bromo-sunrise');

test('saved product gallery has readable previews and available controls after reopening', async ({ page }) => {
  test.setTimeout(90_000);
  await page.goto('/admin/login');
  await page.locator('input[type="email"]').fill('admin@tinggaljalan.test');
  await page.locator('input[type="password"]').fill('password');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/admin\/?$/);
  await page.goto('/admin/tour-packages');
  const editLink = page.getByRole('row').filter({ hasText: packageFixture.title.us })
    .getByRole('link', { name: 'Edit', exact: true });
  const editUrl = new URL(await editLink.getAttribute('href'), page.url());
  editUrl.searchParams.set('tour-package-step', 'form.media-logistics::data::wizard-step');
  await page.goto(editUrl.href);

  const gallery = page.getByRole('group', { name: 'Gallery images', exact: true });
  const items = gallery.locator('.filepond--item');
  for (let visit = 0; visit < 2; visit++) {
    await expect(items).toHaveCount(packageFixture.gallery.length);
    await expect(gallery.locator('.filepond--item[data-filepond-item-state="idle"]'))
      .toHaveCount(packageFixture.gallery.length, { timeout: 30_000 });
    for (const item of await items.all()) {
      await expect(item.getByRole('button', { name: 'Remove', exact: true })).toBeEnabled();
      await expect(item.getByRole('button', { name: 'edit', exact: true })).toBeEnabled();
      // Small screens have two columns: compare against the available tile width.
      await expect.poll(() => item.evaluate(element => {
        const canvas = element.querySelector('canvas');
        const minimum = Math.min(120, element.getBoundingClientRect().width / 2);
        return (canvas?.getBoundingClientRect().height ?? 0) / minimum;
      })).toBeGreaterThan(1);
      await expect.poll(async () => {
        const { width, height } = await item.boundingBox();
        return height / width;
      }).toBeCloseTo(0.75, 1);
    }
    if (visit === 0) await page.reload();
  }
});
