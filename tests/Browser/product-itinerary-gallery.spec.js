import { expect, test } from './fixtures.js';

for (const total of [1, 2, 10, 20]) {
  test(`structured timeline and ${total}-photo gallery remain readable and navigable`, async ({ page }, testInfo) => {
    const originalGalleryRequests = [];
    page.on('request', request => { if (request.url().includes('?gallery=')) originalGalleryRequests.push(request.url()); });
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
    await timeline.screenshot({path:testInfo.outputPath('timeline-detail.png')});
    await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
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
    expect(originalGalleryRequests).toHaveLength(0);
    await gallery.click();
    const dialog = page.getByRole('dialog', { name: 'Trip photo gallery' });
    await expect(dialog).toBeVisible();
    // The portal is visible before its focus/keyboard effect has finished.
    await expect(dialog.getByRole('button', { name: 'Close gallery' })).toBeFocused();
    if (total > 1) {
      await page.keyboard.press('ArrowRight');
      await expect(dialog).toContainText(`1 / ${total}`);
      await dialog.evaluate(element => {
        for (const [type, x] of [['touchstart', 250], ['touchend', 100]]) {
          const event = new Event(type, { bubbles: true });
          Object.defineProperty(event, 'changedTouches', { value: [{ clientX: x }] });
          element.querySelector('div').dispatchEvent(event);
        }
      });
      await expect(dialog).toContainText(`2 / ${total}`);
    }
    await expect.poll(() => dialog.locator('img').first().evaluate(img => img.complete && img.naturalWidth > 0), { timeout: 30000 }).toBe(true);
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
  const consent=page.getByTestId('consent-decline'); if(await consent.isVisible()) await consent.click();
  await timeline.screenshot({path:testInfo.outputPath('bromo-timeline-detail.png')});
  await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
  await page.screenshot({path:testInfo.outputPath('bromo-itinerary.png'),fullPage:true});
});
