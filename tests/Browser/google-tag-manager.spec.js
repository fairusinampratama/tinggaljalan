import { expect, test } from './fixtures.js';

test('GTM respects consent, loads once, and remains blocked after revocation and reload', async ({ page }) => {
  const requests = [];
  await page.route('https://www.googletagmanager.com/**', async (route) => {
    requests.push(route.request().url());
    await route.fulfill({ contentType: 'application/javascript', body: '' });
  });
  await page.goto('/');
  await expect(page.getByTestId('consent-banner')).toBeVisible();
  expect(requests.filter((url) => url.includes('/gtm.js?'))).toHaveLength(0);
  await page.getByTestId('consent-decline').click();
  await page.reload();
  expect(requests.filter((url) => url.includes('/gtm.js?'))).toHaveLength(0);
  await page.evaluate(() => {
    window.TinggalJalanConsent.grant();
    window.TinggalJalanConsent.grant();
  });
  await expect.poll(() => requests.filter((url) => url.includes('/gtm.js?')).length).toBe(1);
  expect(requests.some((url) => url.includes('id=GTM-58KNQXXZ'))).toBe(true);
  const events = await page.evaluate(() => window.dataLayer.map((entry) => entry.event || Array.from(entry).slice(0, 2).join(':')));
  expect(events.indexOf('consent:default')).toBeLessThan(events.indexOf('consent:update'));
  expect(events.indexOf('consent:update')).toBeLessThan(events.indexOf('gtm.js'));
  let routesLink = page.locator('main > nav a[href="/routes"]:visible').first();
  if (await routesLink.count() === 0) {
    await page.getByRole('button', { name: 'Open menu' }).click();
    routesLink = page.locator('main > nav a[href="/routes"]:visible').first();
  }
  await routesLink.click();
  await expect(page).toHaveURL(/\/routes$/);
  expect(requests.filter((url) => url.includes('/gtm.js?'))).toHaveLength(1);
  await page.reload();
  await expect.poll(() => requests.filter((url) => url.includes('/gtm.js?')).length).toBe(2);
  await page.evaluate(() => window.TinggalJalanConsent.deny());
  await page.reload();
  await expect(page.getByTestId('consent-banner')).toHaveCount(0);
  expect(requests.filter((url) => url.includes('/gtm.js?'))).toHaveLength(2);
});
