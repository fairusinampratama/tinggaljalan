import { expect, test } from '@playwright/test';

test('GA4 configures once after consent and survives navigation without reconfiguration', async ({ page }) => {
  const requests = [];
  await page.route('https://www.googletagmanager.com/**', async (route) => {
    requests.push(route.request().url());
    await route.fulfill({ contentType: 'application/javascript', body: '' });
  });
  const configCount = () => page.evaluate(() => window.dataLayer.filter((entry) => entry[0] === 'config' && entry[1] === 'G-1L9N8XTME6').length);
  await page.goto('/');
  expect(await configCount()).toBe(0);
  await expect(page.getByTestId('consent-banner')).toBeVisible();
  expect(requests).toHaveLength(0);
  await page.getByTestId('consent-decline').click();
  await page.reload();
  expect(requests).toHaveLength(0);
  await page.evaluate(() => {
    window.TinggalJalanConsent.grant();
    window.TinggalJalanConsent.grant();
  });
  await expect.poll(() => requests.filter((url) => url.includes('/gtm.js?')).length).toBe(1);
  expect(await configCount()).toBe(1);
  await expect(page.locator('#tinggaljalan-google-ads')).toHaveCount(1);
  await expect(page.locator('#tinggaljalan-google-ads')).toHaveAttribute('src', 'https://www.googletagmanager.com/gtag/js?id=G-1L9N8XTME6');
  expect(requests.filter((url) => url.includes('/gtag/js?'))).toEqual([
    'https://www.googletagmanager.com/gtag/js?id=G-1L9N8XTME6',
  ]);
  expect(await page.evaluate(() => window.dataLayer.filter((entry) => entry[0] === 'config' && entry[1] === 'AW-18427027980').length)).toBe(1);
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
  expect(await configCount()).toBe(1);
  expect(requests.filter((url) => url.includes('/gtm.js?'))).toHaveLength(1);
  await page.reload();
  await expect.poll(() => requests.filter((url) => url.includes('/gtm.js?')).length).toBe(2);
  await page.evaluate(() => window.TinggalJalanConsent.deny());
  await page.reload();
  expect(await configCount()).toBe(0);
  await expect(page.getByTestId('consent-banner')).toHaveCount(0);
  expect(requests.filter((url) => url.includes('/gtm.js?'))).toHaveLength(2);
});
