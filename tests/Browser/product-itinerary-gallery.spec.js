import { expect, test } from '@playwright/test';

for (const total of [1, 2, 10, 20]) {
  test(`structured timeline and ${total}-photo gallery remain readable and navigable`, async ({ page }, testInfo) => {
    await page.route('**/routes/bromo-sunrise', async route => {
      const response = await route.fetch();
      const html = (await response.text()).replace(/(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/,
        (_, start, json, end) => {
          const payload = JSON.parse(json);
          payload.props.route.gallery = Array.from({ length: total }, (_, i) => `${payload.props.route.image}?gallery=${i}`);
          payload.props.route.itinerarySteps = ['23:00', '00:15', '', 'Morning'].map((timeLabel, i) => ({
            id: i + 1, dayNumber: i < 3 ? 1 : 2, displayOrder: i, timeLabel,
            title: { us: `Activity ${i + 1}` }, description: { us: 'A complete description with room for all the details. '.repeat(8) },
          }));
          return start + JSON.stringify(payload).replace(/</g, '\\u003c') + end;
        });
      await route.fulfill({ response, body: html });
    });
    await page.goto('/routes/bromo-sunrise');
    await expect(page.locator('.server-seo-content')).toHaveCount(0);
    const timeline = page.getByTestId('itinerary-timeline');
    await expect(timeline.locator('li')).toHaveCount(4);
    await expect(timeline).toContainText('Day 2');
    await expect(timeline.locator('li').nth(1)).toContainText('00:15');
    await expect(timeline.locator('li').nth(2)).toContainText('Activity 3');
    const consent = page.getByTestId('consent-decline');
    if (await consent.isVisible()) await consent.click();
    await timeline.scrollIntoViewIfNeeded();
    await page.screenshot({ path: testInfo.outputPath('long-multiday-timeline.png'), fullPage: true });
    const gallery = page.getByRole('button', { name: 'Open full-screen gallery', exact: true });
    await gallery.scrollIntoViewIfNeeded();
    const y = await page.evaluate(() => scrollY);
    if (total > 1) {
      await page.getByRole('button', { name: `View photo ${total}`, exact: true }).filter({ visible: true }).evaluate(el => el.click());
      await expect(page.locator('#route-detail')).toContainText(`${total} / ${total}`);
      await expect.poll(() => page.evaluate(() => scrollY)).toBe(y);
      const selected = page.getByRole('button', { name: `View photo ${total}`, exact: true }).filter({ visible: true });
      expect(await selected.evaluate(el => {
        const r = el.getBoundingClientRect(), p = el.parentElement.getBoundingClientRect();
        return r.top >= p.top - 2 && r.bottom <= p.bottom + 2 && r.left >= p.left - 2 && r.right <= p.right + 2;
      })).toBe(true);
    }
    await page.screenshot({ path: testInfo.outputPath('gallery.png') });
    await gallery.click();
    const dialog = page.getByRole('dialog', { name: 'Trip photo gallery' });
    await expect(dialog).toBeVisible();
    if (total > 1) {
      await page.keyboard.press('ArrowRight');
      await expect(dialog).toContainText(`1 / ${total}`);
    }
    await page.screenshot({ path: testInfo.outputPath('fullscreen.png') });
    await page.keyboard.press('Escape');
    await expect(dialog).toHaveCount(0);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
  });
}


test('actual Bromo itinerary retains its complete saved schedule', async ({ page }, testInfo) => {
  await page.goto('/routes/bromo-sunrise');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  const timeline=page.getByTestId('itinerary-timeline');
  await expect(timeline).toBeVisible();
  await timeline.scrollIntoViewIfNeeded();
  await page.screenshot({path:testInfo.outputPath('bromo-itinerary.png'),fullPage:true});
});
