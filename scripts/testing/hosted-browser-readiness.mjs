// Network idle is unrelated to whether the visitor can see the hydrated page.
// The server document is removed only after the app completes its handoff.
export async function waitForHostedHandoff(page, timeout = 30_000) {
    await page.locator('.server-seo-content').waitFor({ state: 'detached', timeout });
    await page.locator('h1').first().waitFor({ state: 'visible', timeout });
}
