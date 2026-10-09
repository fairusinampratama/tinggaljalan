import { expect, test } from '@playwright/test';

test('seven-photo CMS gallery scrolls locally without expanding the package page', async ({ page }, testInfo) => {
  await page.route('**/routes/bromo-sunrise', async route => {
    const response = await route.fetch();
    const html = (await response.text()).replace(/(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/,
      (_, start, json, end) => {
        const payload = JSON.parse(json);
        payload.props.route.gallery = Array.from({ length: 7 }, (_, i) => `${payload.props.route.image}?gallery=${i}`);
        payload.props.route.why = { us: '**Best For** Nature lovers <script>literal</script>' };
        return start + JSON.stringify(payload).replace(/</g, '\\u003c') + end;
      });
    await route.fulfill({ response, body: html });
  });
  await page.goto('/routes/bromo-sunrise');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  const gallery = page.getByRole('button', { name: 'Open full-screen gallery', exact: true });
  await expect(gallery).toBeVisible();
  await expect(page.locator('#route-detail strong').first()).toHaveText('Best For');
  await expect(page.locator('#route-detail')).toContainText('<script>literal</script>');
  const width = page.viewportSize().width;
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width + 1);
  const decline = page.getByTestId('consent-decline');
  if (await decline.isVisible()) await decline.click();
  await gallery.scrollIntoViewIfNeeded();
  const y = await page.evaluate(() => scrollY);
  // DOM click intentionally avoids Playwright scrolling the last thumbnail
  // into the viewport; the component must reveal it inside its own strip.
  await page.getByRole('button', { name: 'View photo 7', exact: true }).filter({ visible: true }).evaluate(el => el.click());
  await expect(page.locator('#route-detail')).toContainText('7 / 7');
  await expect.poll(() => page.evaluate(() => scrollX)).toBe(0);
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(y);
  await page.screenshot({ path: testInfo.outputPath('package-gallery.png'), fullPage: true });
  await gallery.click();
  await expect(page.getByRole('dialog', { name: 'Trip photo gallery' })).toBeVisible();
  await page.getByRole('button', { name: 'Close gallery', exact: true }).click();
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width + 1);
});
