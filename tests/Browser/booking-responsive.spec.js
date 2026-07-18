import { expect, test } from '@playwright/test';

async function expectInsideViewport(locator) {
    await locator.scrollIntoViewIfNeeded();

    const [box, viewport] = await Promise.all([
        locator.boundingBox(),
        locator.page().evaluate(() => ({
            height: window.innerHeight,
            width: window.innerWidth,
        })),
    ]);

    expect(box).not.toBeNull();
    expect(box.x).toBeGreaterThanOrEqual(-1);
    expect(box.x + box.width).toBeLessThanOrEqual(viewport.width + 1);
}

test('booking controls stay inside narrow and desktop viewports', async ({ page }) => {
    await page.goto('/booking');

    const form = page.locator('form').first();
    await expect(form).toBeVisible();

    const routeField = form.getByText('Route', { exact: true }).locator('..');
    const routeTrigger = routeField.getByRole('button');
    await routeTrigger.click();

    const routeOptions = page.getByRole('option');
    const optionCount = await routeOptions.count();
    expect(optionCount).toBeGreaterThan(0);

    let longestOption = 0;
    let longestLabelLength = 0;

    for (let index = 0; index < optionCount; index += 1) {
        const labelLength = (await routeOptions.nth(index).innerText()).length;

        if (labelLength > longestLabelLength) {
            longestLabelLength = labelLength;
            longestOption = index;
        }
    }

    await routeOptions.nth(longestOption).click();

    const increaseGuests = form.getByRole('button', { name: /increase guests/i });
    const pickup = form.getByPlaceholder(/hotel, airport/i);

    await expectInsideViewport(routeTrigger);
    await expectInsideViewport(increaseGuests);
    await expectInsideViewport(pickup);

    const firstAddOn = form.getByRole('checkbox').first();

    if (await firstAddOn.count()) {
        await expectInsideViewport(firstAddOn.locator('..').locator('..'));
    }

    const overflow = await page.evaluate(() => ({
        documentWidth: document.documentElement.scrollWidth,
        viewportWidth: window.innerWidth,
    }));

    expect(overflow.documentWidth).toBeLessThanOrEqual(overflow.viewportWidth + 1);
});
