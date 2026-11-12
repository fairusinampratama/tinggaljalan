import { expect, test } from '@playwright/test';

test('preview displays public business contacts with inert contact and map actions', async ({ page }) => {
    await page.route('**/about-us', async route => {
        const response = await route.fetch();
        const html = await response.text();
        const pageScript = /(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/;
        const match = html.match(pageScript);
        expect(match).not.toBeNull();
        const payload = JSON.parse(match[2]);
        payload.props.publicData.site.contactDetails = {
            email: 'public-business@example.org', address: 'Published business office',
            whatsapp: '628111111111', map_url: 'https://maps.example.org/public-office',
            actionsEnabled: false, emailHref: '#', email_url: '#', whatsapp_url: '#',
        };
        payload.props.publicData.whatsappUrl = '#';
        payload.props.publicData.site.whatsappBaseUrl = '#';
        await route.fulfill({ response, body: html.replace(pageScript, () => `${match[1]}${JSON.stringify(payload)}${match[3]}`) });
    });
    await page.goto('/about-us');
    await expect(page).toHaveURL(/\/about-us$/);
    const footer = page.locator('footer');
    await expect(footer.getByRole('link', { name: 'public-business@example.org', exact: true })).toHaveAttribute('href', '#');
    await expect(footer.locator('address')).toHaveText('Published business office');
    expect(await footer.locator('address').evaluate(address => address.closest('a').getAttribute('href'))).toBe('#');
    await expect(page.locator('a[href^="mailto:"], a[href^="tel:"], a[href^="https://wa.me/"], a[href="https://maps.example.org/public-office"]')).toHaveCount(0);
});
