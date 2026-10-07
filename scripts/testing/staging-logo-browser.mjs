import { chromium } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';

const base = 'https://preview.tinggaljalan.com';
const password = process.env.STAGING_REVIEW_PASSWORD;
if (!password) throw new Error('Missing preview access credential.');
await mkdir('logo-verification', { recursive: true });
const browser = await chromium.launch();
const results = [];
try {
    for (const [name, viewport, isMobile] of [
        ['desktop', { width: 1440, height: 1000 }, false],
        ['mobile', { width: 390, height: 844 }, true],
    ]) {
        const context = await browser.newContext({
            viewport, isMobile,
            httpCredentials: { username: 'reviewer', password, origin: base },
        });
        const page = await context.newPage();
        const response = await page.goto(base, { waitUntil: 'networkidle' });
        if (!response?.ok() || !/noindex/i.test(response.headers()['x-robots-tag'] ?? '')) {
            throw new Error('Preview did not render safely.');
        }
        const result = await page.evaluate(() => {
            const payload = JSON.parse(document.querySelector('script[data-page="app"]').textContent);
            const logo = document.querySelector('nav img[alt="Tinggal Jalan"]');
            return {
                configured: payload.props.publicData.site.logoUrl,
                rendered: logo?.getAttribute('src'),
                loaded: Boolean(logo?.complete && logo.naturalWidth > 0),
                dimensions: logo ? [logo.naturalWidth, logo.naturalHeight] : [],
            };
        });
        if (!result.configured.startsWith('/storage/admin/preview-refresh/') || result.rendered !== result.configured || !result.loaded) {
            throw new Error('Navbar failed to display the copied public logo.');
        }
        await page.screenshot({ path: `logo-verification/${name}-logo.png` });
        results.push({ viewport: name, ...result });
        await context.close();
    }
    await writeFile('logo-verification/results.json', JSON.stringify(results, null, 2));
    console.log('Desktop and mobile navbar both display the copied production logo.');
} finally {
    await browser.close();
}
