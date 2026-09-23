import { expect, test } from '@playwright/test';

async function declineOptionalConsent(page) {
    const decline = page.getByTestId('consent-decline');

    if (await decline.isVisible()) {
        await decline.click();
    }
}

test('route policies render as responsive localized bullet lists', async ({ page }) => {
    await page.goto('/routes/bromo-sunrise');
    await declineOptionalConsent(page);

    const policies = page.getByTestId('route-policies');
    const cancellation = page.getByTestId('cancellation-policy');
    const confirmation = page.getByTestId('confirmation-policy');

    await expect(policies).toBeVisible();
    await expect(cancellation.getByRole('heading', { name: 'Cancellation policy' })).toBeVisible();
    await expect(cancellation.getByRole('listitem')).toHaveCount(2);
    await expect(confirmation.getByRole('heading', { name: 'Confirmation' })).toBeVisible();
    await expect(confirmation.getByRole('listitem')).toHaveCount(1);

    const overflow = await page.evaluate(() => ({
        documentWidth: document.documentElement.scrollWidth,
        viewportWidth: window.innerWidth,
    }));
    expect(overflow.documentWidth).toBeLessThanOrEqual(overflow.viewportWidth + 1);

    await page.goto('/language/id');
    await page.goto('/routes/bromo-sunrise');

    await expect(page.getByTestId('cancellation-policy').getByRole('heading', { name: 'Kebijakan pembatalan' })).toBeVisible();
    await expect(page.getByTestId('cancellation-policy')).toContainText('Bisa batal gratis sampai 24 jam sebelum pickup');

    await page.goto('/language/cn');
    await page.goto('/routes/bromo-sunrise');

    await expect(page.getByTestId('cancellation-policy').getByRole('heading', { name: '取消政策' })).toBeVisible();
    await expect(page.getByTestId('confirmation-policy').getByRole('heading', { name: '确认' })).toBeVisible();
});
