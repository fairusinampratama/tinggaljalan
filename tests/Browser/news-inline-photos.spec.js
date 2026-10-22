import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import path from 'node:path';

const story = '/news/inline-editorial-photo-story';
const photoSelector = '.news-section-photo';
test.beforeAll(() => {
  execFileSync('php', ['tests/Fixtures/news-inline-photos.php'], { env: {
    ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'sqlite',
    DB_DATABASE: path.resolve('storage/framework/testing/browser.sqlite'), CACHE_STORE: 'array',
  } });
});

test('inline photographs match initial HTML after React mount, including translations and navigation', async ({ page, browser }, testInfo) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const serverContext = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 1440, height: 1000 } });
  const server = await serverContext.newPage();
  await server.goto(story + '?lang=cn');
  const expected = await server.locator(photoSelector).evaluateAll(figures => figures.map(figure => ({
    section: figure.closest('section').id, src: figure.querySelector('img').getAttribute('src'),
    alt: figure.querySelector('img').alt, caption: figure.querySelector('figcaption')?.textContent ?? '',
  })));
  expect(expected).toHaveLength(3);
  await page.goto(story + '?lang=cn');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  await expect(page.locator(photoSelector)).toHaveCount(3);
  const actual = await page.locator(photoSelector).evaluateAll(figures => figures.map(figure => ({
    section: figure.closest('section').id, src: figure.querySelector('img').getAttribute('src'),
    alt: figure.querySelector('img').alt, caption: figure.querySelector('figcaption')?.textContent ?? '',
  })));
  expect(actual).toEqual(expected);
  await expect(page.locator(`${photoSelector} figcaption`)).toHaveCount(2);
  for (const figure of await page.locator(photoSelector).all()) {
    await figure.scrollIntoViewIfNeeded();
    const img = figure.locator('img');
    await expect(img).toHaveAttribute('loading', 'lazy');
    await expect.poll(() => img.evaluate(image => image.complete && image.naturalWidth > 0)).toBe(true);
    await expect(figure.locator('source')).toHaveAttribute('srcset', /-480.webp 480w/);
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
  const links = page.locator('a[href="#a-slower-journey-home"]');
  if (await links.first().isVisible()) {
    await links.first().click();
    await expect(page.locator('#a-slower-journey-home')).toBeInViewport();
  }
  await expect(page.locator('meta[property="og:image"]')).toHaveAttribute('content', await server.locator('meta[property="og:image"]').getAttribute('content'));
  await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', await server.locator('link[rel="canonical"]').getAttribute('href'));
  await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'noindex,follow');
  expect(errors).toEqual([]);
  if (await page.getByTestId('consent-decline').isVisible()) await page.getByTestId('consent-decline').click();
  await page.goto(story + '?lang=us');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  await page.addStyleTag({ content: 'html { scroll-behavior: auto !important; }' });
  for (const figure of await page.locator(photoSelector).all()) {
    await figure.scrollIntoViewIfNeeded();
    await expect.poll(() => figure.locator('img').evaluate(image => image.complete && image.naturalWidth > 0)).toBe(true);
  }
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: testInfo.outputPath('inline-photos.png'), fullPage: true });
  await page.locator('#morning-on-the-mountain').evaluate(section => window.scrollTo(0, section.getBoundingClientRect().top + window.scrollY - 110));
  await page.screenshot({ path: testInfo.outputPath('inline-section.png') });
  await page.locator(photoSelector).nth(1).evaluate(figure => window.scrollTo(0, figure.getBoundingClientRect().top + window.scrollY - 110));
  await page.screenshot({ path: testInfo.outputPath('portrait-photo.png') });
  await serverContext.close();
});

test('older articles have no inline figures and keep their cover', async ({ page }, testInfo) => {
  await page.goto('/news/paket-wisata-bromo-dari-malang');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  await expect(page.locator(photoSelector)).toHaveCount(0);
  await expect(page.locator('article header + div img')).toBeVisible();
  await expect(page.locator('article section[id]').first()).toBeVisible();
  await page.screenshot({ path: testInfo.outputPath('older-article.png') });
});

test('admin can preview and save existing nested photographs without losing them', async ({ page }, testInfo) => {
  await page.goto('/admin/login');
  await page.locator('input[type="email"]').fill('admin@tinggaljalan.test');
  await page.locator('input[type="password"]').fill('password');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/admin\/?$/);
  await page.goto('/admin/news-articles');
  const row = page.getByRole('row').filter({ hasText: 'From mountain light to a green valley' });
  await row.getByRole('link', { name: 'Edit', exact: true }).click();
  if (!(await page.getByText('Section photographs', { exact: true }).first().isVisible())) {
    await page.getByRole('button', { name: 'Next', exact: true }).click();
  }
  await expect(page.getByText('Section photographs', { exact: true }).first()).toBeVisible();
  await expect(page.locator('input[id$=".alt.us"]').first()).toHaveValue('Morning light over Mount Bromo');
  const caption = page.locator('textarea[id$=".caption.us"]').first();
  await expect(caption).toHaveValue('The landscape opens up as the morning light reaches the caldera.');
  await caption.fill('The landscape opens up as the morning light reaches the caldera.');
  await page.locator('input[id$=".alt.us"]').first().scrollIntoViewIfNeeded();
  await page.screenshot({ path: testInfo.outputPath('admin-section-photos.png') });
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page.getByText('Saved', { exact: true })).toBeVisible();
  await page.reload();
  if (!(await page.getByText('Section photographs', { exact: true }).first().isVisible())) {
    await page.getByRole('button', { name: 'Next', exact: true }).click();
  }
  await expect(page.locator('input[id$=".alt.us"]').first()).toHaveValue('Morning light over Mount Bromo');
});
