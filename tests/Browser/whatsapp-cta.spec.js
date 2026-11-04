import { expect, test } from '@playwright/test';

const floating = page => page.locator('[data-whatsapp-placement="floating"]');
async function ready(page, path = '/') {
  await page.goto(path);
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  const decline = page.getByTestId('consent-decline');
  if (await decline.isVisible()) await decline.click();
}
async function insideViewport(page, locator) {
  const box = await locator.boundingBox();
  const viewport = page.viewportSize();
  expect(box).not.toBeNull();
  expect(box.x).toBeGreaterThanOrEqual(0);
  expect(box.y).toBeGreaterThanOrEqual(0);
  expect(box.x + box.width).toBeLessThanOrEqual(viewport.width + 1);
  expect(box.y + box.height).toBeLessThanOrEqual(viewport.height + 1);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
}

test('floating WhatsApp has a clear name, responsive dimensions, safe focus and unchanged destinations', async ({ page }, testInfo) => {
  await ready(page);
  const link = floating(page);
  await expect(link).toHaveAccessibleName('Chat via WhatsApp');
  await expect(link).toHaveAttribute('target', '_blank');
  await expect(link).toHaveAttribute('rel', 'noreferrer');
  await expect(link).toHaveAttribute('href', await page.locator('[data-whatsapp-placement="footer"]').getAttribute('href'));
  const width = page.viewportSize().width;
  const box = await link.boundingBox();
  expect(box.height).toBe(width < 640 ? 56 : 48);
  if (width < 640) expect(box.width).toBe(56);
  const icon = link.locator('img');
  await expect(icon).toHaveAttribute('src', '/images/brand/whatsapp-glyph-white.svg');
  expect((await icon.boundingBox()).width).toBe(width < 640 ? 28 : 24);
  await expect.poll(() => icon.evaluate(el => el.complete && el.naturalWidth > 0)).toBe(true);
  await insideViewport(page, link);
  // Focus with the keyboard, without opening WhatsApp or sending a message.
  await page.getByRole('link', { name: 'Back to top' }).focus();
  await page.keyboard.press('Tab');
  await expect(link).toBeFocused();
  expect(await link.evaluate(el => getComputedStyle(el).outlineStyle)).toBe('solid');
  await page.screenshot({ path: testInfo.outputPath('homepage-whatsapp.png') });
  await page.getByRole('button', { name: 'Cookie settings', exact: true }).click();
  await expect(page.getByTestId('consent-banner')).toBeVisible();
  await expect(link).toBeHidden();
  await page.getByTestId('consent-decline').click();
  await expect(link).toBeVisible();
  if (width < 1024) {
    await page.getByRole('button', { name: 'Open menu' }).click();
    await expect(link).toBeHidden();
    await page.getByRole('button', { name: 'Close menu' }).click();
    await expect(link).toBeVisible();
  }
  await ready(page, '/about-us');
  await expect(floating(page)).toBeVisible();
  await insideViewport(page, floating(page));
});

test('route mobile bar is the single persistent contact action and yields to overlays', async ({ page }, testInfo) => {
  await ready(page, '/routes/bromo-sunrise');
  const mobile = page.locator('[data-whatsapp-placement="route-mobile"]');
  const desktop = page.locator('[data-whatsapp-placement="route-sidebar"]');
  const isSmall = page.viewportSize().width < 1024;
  await expect(mobile).toHaveAttribute('href', await desktop.getAttribute('href'));
  if (isSmall) {
    await expect(floating(page)).toBeHidden();
    await expect(mobile).toBeVisible();
    expect((await mobile.boundingBox()).height).toBeGreaterThanOrEqual(48);
    await insideViewport(page, mobile);
    await page.getByRole('button', { name: 'Open menu' }).click();
    await expect(mobile).toBeHidden();
    await page.getByRole('button', { name: 'Close menu' }).click();
    await expect(mobile).toBeVisible();
  } else {
    await expect(mobile).toBeHidden();
    await expect(floating(page)).toBeVisible();
  }
  await page.screenshot({ path: testInfo.outputPath('route-whatsapp.png') });
  await page.getByRole('button', { name: 'Cookie settings', exact: true }).click();
  await expect(floating(page)).toBeHidden();
  await expect(mobile).toBeHidden();
});

for (const [locale, label] of [['id', 'Chat melalui WhatsApp'], ['cn', '通过 WhatsApp 咨询']]) {
  test(`WhatsApp accessible label follows ${locale} localization`, async ({ page }) => {
    await ready(page);
    await page.goto(`/language/${locale}`);
    await expect(floating(page)).toHaveAccessibleName(label);
  });
}

test('placement metadata alone emits no custom events or sensitive fields', async ({ page }) => {
  await page.route('https://www.googletagmanager.com/**', route => route.fulfill({ contentType: 'application/javascript', body: '' }));
  await ready(page);
  // The homepage CTA is in a lazy Suspense section; wait for its real mount.
  await expect(page.locator('[data-whatsapp-placement="homepage"]')).toHaveCount(1);
  await page.evaluate(() => window.TinggalJalanConsent.grant());
  const before = await page.evaluate(() => window.dataLayer.length);
  // Stop navigation at the document edge while exercising the real DOM click.
  await floating(page).evaluate(el => el.addEventListener('click', event => event.preventDefault(), { once: true }));
  await floating(page).click();
  expect(await page.evaluate(() => window.dataLayer.length)).toBe(before);
  expect(await page.locator('[data-whatsapp-placement]').evaluateAll(els => els.map(el => el.dataset.whatsappPlacement))).toEqual(expect.arrayContaining(['floating', 'homepage', 'footer']));
});
