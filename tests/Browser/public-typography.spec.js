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
      textWrap: styles.textWrap,
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

async function expectInteractiveTypographyContained(page) {
  const overflowing = await page.locator('.type-button, .type-button-compact, .type-control').evaluateAll((elements) =>
    elements
      .filter((element) => element.getBoundingClientRect().width > 0)
      .filter((element) => element.scrollWidth > element.clientWidth + 1)
      .map((element) => element.textContent?.trim()).filter(Boolean)
  );

  expect(overflowing).toEqual([]);
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
  const sectionTitle = await computedTypography(page.locator('.type-section-title').first());
  const editorialCard = await computedTypography(page.locator('.type-editorial-card').first());
  const productTitle = await computedTypography(page.locator('.type-product-title').first());
  const body = await computedTypography(page.locator('.type-body').first());
  const bodyCompact = await computedTypography(page.locator('.type-body-compact').first());
  const control = await computedTypography(page.locator('.type-control').first());
  const button = await computedTypography(page.locator('.type-button').first());

  expect(nav.family).toContain('Inter');
  expect(nav.size).toBe('14px');
  expect(nav.weight).toBe('500');
  expect(productTitle.family).toContain('Inter');
  expect(productTitle.size).toBe(desktop ? '15px' : '14px');
  expect(productTitle.weight).toBe('600');
  expect(Number.parseFloat(productTitle.lineHeight)).toBeCloseTo(desktop ? 20.25 : 18.9, 2);
  expect(sectionTitle.family).toContain('Fraunces');
  expect(sectionTitle.size).toBe(desktop ? '34px' : '28px');
  expect(sectionTitle.weight).toBe('500');
  expect(Number.parseFloat(sectionTitle.lineHeight)).toBeCloseTo(desktop ? 36.72 : 30.24, 2);
  expect(sectionTitle.textWrap).toBe('balance');
  expect(editorialCard.family).toContain('Fraunces');
  expect(editorialCard.size).toBe('22px');
  expect(editorialCard.weight).toBe('500');
  expect(Number.parseFloat(editorialCard.lineHeight)).toBeCloseTo(26.4, 2);
  expect(body.family).toContain('Inter');
  expect(body.size).toBe('16px');
  expect(body.weight).toBe('400');
  expect(Number.parseFloat(body.lineHeight)).toBeCloseTo(24.8, 2);
  expect(bodyCompact.family).toContain('Inter');
  expect(bodyCompact.size).toBe('14px');
  expect(bodyCompact.weight).toBe('400');
  expect(Number.parseFloat(bodyCompact.lineHeight)).toBeCloseTo(21, 2);
  expect(control.size).toBe('14px');
  expect(control.weight).toBe('600');
  expect(Number.parseFloat(control.lineHeight)).toBeCloseTo(20, 2);
  expect(button.size).toBe('16px');
  expect(button.weight).toBe('600');
  expect(Number.parseFloat(button.lineHeight)).toBeCloseTo(24, 2);
  await expectInteractiveTypographyContained(page);

  await page.goto('/routes/bromo-sunrise');
  await page.evaluate(() => document.fonts.ready);

  const detailTitle = await computedTypography(page.locator('.type-detail-title').first());
  const detailSection = await computedTypography(page.locator('.type-detail-section').first());

  expect(detailTitle.family).toContain('Fraunces');
  expect(detailTitle.size).toBe(desktop ? '48px' : '32px');
  expect(detailTitle.weight).toBe('500');
  expect(Number.parseFloat(detailTitle.lineHeight)).toBeCloseTo(desktop ? 50.4 : 33.6, 2);
  expect(detailSection.family).toContain('Fraunces');
  expect(detailSection.size).toBe('26px');
  expect(detailSection.weight).toBe('500');
  expect(Number.parseFloat(detailSection.lineHeight)).toBeCloseTo(31.2, 2);

  const price = await computedTypography(page.locator('.type-price').first());
  expect(price.family).toContain('Inter');
  expect(price.size).toBe('24px');
  expect(price.weight).toBe('700');
  expect(Number.parseFloat(price.lineHeight)).toBeCloseTo(24, 2);
  await expectInteractiveTypographyContained(page);
  await expectNoHorizontalOverflow(page);
});

test('Chinese pages use the explicit system CJK stack', async ({ page }) => {
  await page.goto('/?lang=cn');
  await page.evaluate(() => document.fonts.ready);

  await expect(page.locator('html')).toHaveAttribute('lang', 'zh-CN');
  const body = await computedTypography(page.locator('body'));
  const heroHeading = await computedTypography(page.locator('.type-home-hero').first());

  expect(body.family).toContain('PingFang SC');
  expect(body.family).toContain('Microsoft YaHei');
  expect(heroHeading.family).toContain('PingFang SC');
  expect(heroHeading.family).not.toContain('Fraunces');
  await expectInteractiveTypographyContained(page);
  await expectNoHorizontalOverflow(page);
});

test('public typography remains contained across representative routes', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'Broad route audit runs once in desktop Chromium.');
  test.setTimeout(60_000);

  for (const path of publicRoutes) {
    await page.goto(path);
    await page.evaluate(() => document.fonts.ready);

    expect((await computedTypography(page.locator('body'))).family).toContain('Inter');
    expect(await page.locator('[class*="public-heading-"]').count()).toBe(0);
    await expectInteractiveTypographyContained(page);
    await expectNoHorizontalOverflow(page);
  }

  await page.goto('/booking?route=jogja-heritage&lang=us');
  const decline = page.getByTestId('consent-decline');
  if (await decline.isVisible()) await decline.click();
  await page.getByRole('button', { name: /continue to contact/i }).click();
  await expect(page).toHaveURL(/\/checkout\/review/);
  expect((await computedTypography(page.locator('body'))).family).toContain('Inter');
  await expectNoHorizontalOverflow(page);

  for (const language of ['us', 'id', 'cn']) {
    await page.goto(`/routes?lang=${language}`);
    await page.evaluate(() => document.fonts.ready);
    await expectInteractiveTypographyContained(page);
    await expectNoHorizontalOverflow(page);
  }
});

test('Filament admin keeps Manrope', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'Admin font isolation only needs one browser.');

  await page.goto('/admin/login');
  await page.evaluate(() => document.fonts.ready);

  expect((await computedTypography(page.locator('body'))).family).toContain('Manrope');
  expect(await page.evaluate(() => document.fonts.check('16px Manrope'))).toBe(true);
});
