import { expect, test } from '@playwright/test';

const publicRoutes = [
  '/',
  '/routes',
  '/routes/bromo-sunrise',
  '/news',
  '/news/paket-wisata-bromo-dari-malang',
  '/booking',
  '/about-us',
  '/privacy-policy',
];

async function computedTypography(locator) {
  return locator.evaluate((element) => {
    const styles = window.getComputedStyle(element);

    return {
      family: styles.fontFamily,
      size: styles.fontSize,
      weight: styles.fontWeight,
      lineHeight: styles.lineHeight,
      letterSpacing: styles.letterSpacing,
    };
  });
}

async function expectNoHorizontalOverflow(page) {
  const dimensions = await page.evaluate(() => ({
    documentWidth: document.documentElement.scrollWidth,
    viewportWidth: window.innerWidth,
  }));

  expect(dimensions.documentWidth).toBeLessThanOrEqual(dimensions.viewportWidth + 1);
}

test('public pages use locally hosted Inter and Fraunces', async ({ page }) => {
  const fontRequests = [];

  page.on('request', (request) => {
    if (request.resourceType() === 'font') fontRequests.push(request.url());
  });

  await page.goto('/');
  await page.evaluate(async () => {
    await document.fonts.ready;
    await Promise.all([
      document.fonts.load('400 16px Inter'),
      document.fonts.load('500 16px Fraunces'),
    ]);
  });

  const body = await computedTypography(page.locator('body'));
  const heroHeading = await computedTypography(page.locator('#home .type-home-hero').first());
  const desktop = (page.viewportSize()?.width ?? 0) >= 640;

  expect(body.family).toContain('Inter');
  expect(heroHeading.family).toContain('Fraunces');
  expect(heroHeading.weight).toBe('500');
  expect(heroHeading.size).toBe(desktop ? '44px' : '31px');
  expect(Number.parseFloat(heroHeading.lineHeight)).toBeCloseTo(desktop ? 48.4 : 34.1, 2);
  expect(heroHeading.letterSpacing).toBe(desktop ? '-0.8px' : '-0.5px');
  await expect.poll(() => page.evaluate(() => ({
    inter: document.fonts.check('400 16px Inter'),
    fraunces: document.fonts.check('500 16px Fraunces'),
  }))).toEqual({ inter: true, fraunces: true });
  expect(fontRequests.length).toBeGreaterThan(0);
  expect(fontRequests.every((url) => new URL(url).origin === new URL(page.url()).origin)).toBe(true);
  await expectNoHorizontalOverflow(page);
});

test('semantic typography matches the Trivpass role metrics', async ({ page }) => {
  const desktop = (page.viewportSize()?.width ?? 0) >= 640;

  await page.goto('/');
  await page.evaluate(() => document.fonts.ready);

  const nav = await computedTypography(page.locator('.type-nav').first());
  const sectionTitle = await computedTypography(page.locator('.type-section-title, .public-heading-section').first());
  const productTitle = await computedTypography(page.locator('.type-product-title').first());

  expect(nav.family).toContain('Inter');
  expect(nav.size).toBe('14px');
  expect(nav.weight).toBe('500');
  expect(productTitle.family).toContain('Inter');
  expect(productTitle.size).toBe(desktop ? '15px' : '14px');
  expect(productTitle.weight).toBe('600');
  expect(sectionTitle.family).toContain('Fraunces');
  expect(sectionTitle.size).toBe(desktop ? '34px' : '28px');
  expect(sectionTitle.weight).toBe('500');

  await page.goto('/routes/bromo-sunrise');
  await page.evaluate(() => document.fonts.ready);

  const detailTitle = await computedTypography(page.locator('.type-detail-title').first());
  const detailSection = await computedTypography(page.locator('.type-detail-section').first());

  expect(detailTitle.family).toContain('Fraunces');
  expect(detailTitle.size).toBe(desktop ? '48px' : '32px');
  expect(detailTitle.weight).toBe('500');
  expect(detailSection.family).toContain('Fraunces');
  expect(detailSection.size).toBe('26px');
  expect(detailSection.weight).toBe('500');
  await expectNoHorizontalOverflow(page);
});

test('public typography remains contained across representative routes', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'Broad route audit runs once in desktop Chromium.');

  for (const path of publicRoutes) {
    await page.goto(path);
    await page.evaluate(() => document.fonts.ready);

    expect((await computedTypography(page.locator('body'))).family).toContain('Inter');
    await expectNoHorizontalOverflow(page);
  }

  await page.goto('/booking?route=jogja-heritage');
  const decline = page.getByTestId('consent-decline');
  if (await decline.isVisible()) await decline.click();
  await page.getByRole('button', { name: /continue to contact/i }).click();
  await expect(page).toHaveURL(/\/checkout\/review/);
  expect((await computedTypography(page.locator('body'))).family).toContain('Inter');
  await expectNoHorizontalOverflow(page);
});

test('Filament admin keeps Manrope', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'Admin font isolation only needs one browser.');

  await page.goto('/admin/login');
  await page.evaluate(() => document.fonts.ready);

  expect((await computedTypography(page.locator('body'))).family).toContain('Manrope');
  expect(await page.evaluate(() => document.fonts.check('16px Manrope'))).toBe(true);
});
