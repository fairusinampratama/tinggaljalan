import { expect, test } from '@playwright/test';

async function declineOptionalConsent(page) {
    const decline = page.getByTestId('consent-decline');

    if (await decline.isVisible()) {
        await decline.click();
    }
}

test('homepage promotion is responsive, copyable, and replaces the trust strip', async ({ page }) => {
    await page.goto('/');
    await declineOptionalConsent(page);

    const section = page.getByTestId('promotions-section');
    await expect(section).toBeVisible();
    await expect(section.getByRole('heading', { name: 'Current promotions' })).toBeVisible();
    await expect(page.getByText('Google Reviews')).toHaveCount(0);

    const cards = page.getByTestId('promotion-card');
    expect(await cards.count()).toBeGreaterThan(0);
    await expect(cards.first()).toContainText('BROMO10');

    await page.getByTestId('copy-promotion-BROMO10').click();
    await expect(page.getByTestId('copy-promotion-BROMO10')).toContainText('Copied');

    const firstCardBox = await cards.first().boundingBox();
    const viewport = page.viewportSize();
    expect(firstCardBox).not.toBeNull();
    expect(firstCardBox.x).toBeGreaterThanOrEqual(0);
    expect(firstCardBox.x + firstCardBox.width).toBeLessThanOrEqual(viewport.width + 1);

    const overflow = await page.evaluate(() => ({
        documentWidth: document.documentElement.scrollWidth,
        viewportWidth: window.innerWidth,
    }));
    expect(overflow.documentWidth).toBeLessThanOrEqual(overflow.viewportWidth + 1);
});

test('Indonesian homepage shows the localized IDR promotion', async ({ page }) => {
    await page.goto('/language/id');
    await page.goto('/');
    await declineOptionalConsent(page);

    await expect(page.getByRole('heading', { name: 'Promo saat ini' })).toBeVisible();
    await expect(page.getByTestId('promotion-card')).toHaveCount(2);
    await expect(page.getByText('TJHEMAT')).toBeVisible();
    await expect(page.getByText('Rp50K')).toBeVisible();
});
