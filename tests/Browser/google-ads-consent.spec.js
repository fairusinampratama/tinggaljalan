import { expect, test } from '@playwright/test';

const googleTagPattern = 'https://www.googletagmanager.com/gtag/js**';

async function mockGoogleTag(page) {
  const requests = [];

  await page.route(googleTagPattern, async (route) => {
    requests.push(route.request().url());
    await route.fulfill({
      contentType: 'application/javascript',
      body: '',
    });
  });

  return requests;
}

async function adsConfigCount(page) {
  return page.evaluate(() => window.dataLayer
    .map((entry) => Array.from(entry))
    .filter(([command, id]) => command === 'config' && id === 'AW-18427027980')
    .length);
}

async function visitRoutesWithInertia(page) {
  let routesLink = page.locator('main > nav a[href="/routes"]:visible').first();

  if (await routesLink.count() === 0) {
    await page.getByRole('button', { name: 'Open menu' }).click();
    routesLink = page.locator('main > nav a[href="/routes"]:visible').first();
  }

  await routesLink.click();
}

test.describe('Google Ads consent', () => {
  test('does not contact Google before a visitor decides', async ({ page }) => {
    const requests = await mockGoogleTag(page);

    await page.goto('/');

    await expect(page.getByTestId('consent-banner')).toBeVisible();
    await page.waitForTimeout(250);
    expect(requests).toHaveLength(0);
    await expect.poll(() => adsConfigCount(page)).toBe(0);
  });

  test('declining persists and prevents loading after reload', async ({ page }) => {
    const requests = await mockGoogleTag(page);

    await page.goto('/');
    await page.getByTestId('consent-decline').click();

    await expect(page.getByTestId('consent-banner')).toHaveCount(0);
    await expect.poll(() => page.evaluate(() => window.TinggalJalanConsent.status())).toBe('denied');
    await page.reload();
    await expect(page.getByTestId('consent-banner')).toHaveCount(0);
    expect(requests).toHaveLength(0);
  });

  test('accepting loads and configures the Ads tag once across Inertia navigation', async ({ page }) => {
    const requests = await mockGoogleTag(page);

    await page.goto('/');
    await page.getByTestId('consent-allow').click();

    await expect.poll(() => requests.length).toBe(1);
    await expect.poll(() => adsConfigCount(page)).toBe(1);
    await expect(page.locator('#tinggaljalan-google-ads')).toHaveCount(1);

    await visitRoutesWithInertia(page);
    await expect(page).toHaveURL(/\/routes$/);
    await expect(page.getByTestId('consent-banner')).toHaveCount(0);
    expect(requests).toHaveLength(1);
    expect(await adsConfigCount(page)).toBe(1);
    await expect(page.locator('#tinggaljalan-google-ads')).toHaveCount(1);
  });

  test('footer settings reopens the choice and supports keyboard revocation', async ({ page }) => {
    const requests = await mockGoogleTag(page);

    await page.goto('/');
    await page.getByTestId('consent-allow').focus();
    await expect(page.getByTestId('consent-allow')).toBeFocused();
    await page.keyboard.press('Enter');
    await expect.poll(() => requests.length).toBe(1);

    await page.getByTestId('cookie-settings').scrollIntoViewIfNeeded();
    await page.getByTestId('cookie-settings').focus();
    await page.keyboard.press('Enter');

    await expect(page.getByTestId('consent-banner')).toBeVisible();
    await page.getByTestId('consent-decline').focus();
    await page.keyboard.press('Enter');
    await expect.poll(() => page.evaluate(() => window.TinggalJalanConsent.status())).toBe('denied');

    const latestConsent = await page.evaluate(() => {
      const consentEntries = window.dataLayer
        .map((entry) => Array.from(entry))
        .filter(([command, action]) => command === 'consent' && action === 'update');

      return consentEntries.at(-1)?.[2];
    });
    expect(latestConsent).toMatchObject({
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      analytics_storage: 'denied',
    });

    await page.reload();
    await expect(page.getByTestId('consent-banner')).toHaveCount(0);
    expect(requests).toHaveLength(1);
  });
});
