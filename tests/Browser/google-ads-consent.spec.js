import { expect, test } from './fixtures.js';

const googleTagPattern = 'https://www.googletagmanager.com/gtag/js**';

async function mockGoogleTag(page) {
  const requests = [];

  await page.route('https://www.googletagmanager.com/gtm.js**', (route) => route.fulfill({ contentType: 'application/javascript', body: '' }));

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
  test('loads the Google library with denied defaults before a visitor decides', async ({ page }) => {
    const requests = await mockGoogleTag(page);

    await page.goto('/');

    await expect(page.getByTestId('consent-banner')).toBeVisible();
    await page.waitForTimeout(250);
    await expect.poll(() => requests.length).toBe(1);
    await expect.poll(() => adsConfigCount(page)).toBe(1);
    const commands = await page.evaluate(() => window.dataLayer.filter((entry) => entry[0]).map((entry) => Array.from(entry)));
    expect(commands[0].slice(0, 2)).toEqual(['consent', 'default']);
    expect(commands[0][2]).toEqual({ ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'denied' });
    await expect(page.locator('#tinggaljalan-gtm')).toHaveCount(0);
    await expect(page.getByRole('link', { name: /learn more/i })).toHaveAttribute('href', '/privacy-policy');
  });

  test('declining persists with denied consent after reload', async ({ page }) => {
    const requests = await mockGoogleTag(page);

    await page.goto('/');
    await page.getByTestId('consent-decline').click();

    await expect(page.getByTestId('consent-banner')).toHaveCount(0);
    await expect.poll(() => page.evaluate(() => window.TinggalJalanConsent.status())).toBe('denied');
    await page.reload();
    await expect(page.getByTestId('consent-banner')).toHaveCount(0);
    await expect.poll(() => requests.length).toBe(2);
    const consent = await page.evaluate(() => window.dataLayer.filter((entry) => entry[0] === 'consent').map((entry) => Array.from(entry)));
    expect(Object.values(consent.at(-1)[2])).toEqual(['denied', 'denied', 'denied', 'denied']);
    await expect(page.locator('#tinggaljalan-gtm')).toHaveCount(0);
  });

  test('accepting updates consent without reconfiguring Ads across Inertia navigation', async ({ page }) => {
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
    await expect(page.getByTestId('consent-banner')).toBeVisible();
    await page.getByTestId('consent-allow').focus();
    await expect(page.getByTestId('consent-allow')).toBeFocused();
    await page.keyboard.press('Enter');
    await expect.poll(() => requests.length).toBe(1);

    await page.getByTestId('cookie-settings').scrollIntoViewIfNeeded();
    await page.getByTestId('cookie-settings').focus();
    await page.keyboard.press('Enter');

    await expect(page.getByTestId('consent-banner')).toBeVisible();
    await expect(page.getByTestId('consent-banner')).toBeFocused();
    await expect(page.getByTestId('consent-status')).toContainText('Allowed');
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
    await expect.poll(() => requests.length).toBe(2);
    await expect(page.locator('#tinggaljalan-gtm')).toHaveCount(0);
  });

  test('privacy policy is reachable and can reopen saved settings', async ({ page }) => {
    await mockGoogleTag(page);

    await page.goto('/');
    await page.getByTestId('consent-decline').click();
    await page.goto('/privacy-policy');

    await expect(page.getByRole('heading', { name: 'Privacy and cookie policy' })).toBeVisible();
    await page.getByTestId('privacy-cookie-settings').click();
    await expect(page.getByTestId('consent-banner')).toBeFocused();
    await expect(page.getByTestId('consent-status')).toContainText('Declined');
  });
});
