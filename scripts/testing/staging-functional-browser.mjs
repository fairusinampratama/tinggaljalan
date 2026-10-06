import { chromium, expect } from '@playwright/test';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
const [file, revision, output, run] = process.argv.slice(2);
if (!file || !/^[a-f0-9]{40}$/.test(revision ?? '') || !output || !/^\d+$/.test(run ?? '')) throw new Error('Invalid functional check arguments.');
const config = JSON.parse(await readFile(file, 'utf8'));
const base = 'https://preview.tinggaljalan.com';
await mkdir(output, { recursive: true, mode: 0o700 });
const browser = await chromium.launch();
const results = [];
let stage = "runtime";
let currentPage;
try {
    for (const [name, viewport] of [['desktop', {width:1440,height:1000}], ['mobile', {width:390,height:844}]]) {
        const context = await browser.newContext({viewport, httpCredentials:{username:'reviewer',password:config.review_password,origin:base}});
        const health = await (await context.request.get(`${base}/up?revision=${revision}`)).json();
        if (health.status !== 'up' || health.revision !== revision) throw new Error('Unexpected preview revision.');
        const page = await context.newPage();
        currentPage = page;
        let jsErrors = 0;
        page.on('pageerror', () => jsErrors++);
        // Block every external request: these checks must not contact payment or messaging services.
        await context.route('**/*', route => new URL(route.request().url()).origin === base ? route.continue() : route.abort());
        stage = `${name}: booking form`;
        await page.goto(`${base}/language/us`);
        await page.goto(`${base}/booking?route=jogja-heritage`, {waitUntil:'networkidle'});
        const consent = page.getByTestId('consent-decline');
        if (await consent.isVisible()) await consent.click();
        await page.getByPlaceholder(/hotel, airport/i).fill(`SYNTHETIC CHECK ${run} ${name}`);
        await page.getByRole('button', {name:/continue to contact/i}).click();
        await expect(page).toHaveURL(/\/checkout\/review$/);
        const fixture = `preview-check-${run}-${name}`;
        await page.locator('input[autocomplete="name"]').fill(`Synthetic Preview ${run} ${name}`);
        await page.getByLabel('WhatsApp number', {exact:true}).fill('81234567890');
        await page.locator('input[type="email"]').fill(`${fixture}@tinggaljalan.test`);
        await page.locator('textarea').fill('SYNTHETIC PREVIEW TEST. No travel, payment, email or WhatsApp requested.');
        await page.screenshot({path:path.join(output,`${name}-booking-contact.png`),fullPage:true});
        stage = `${name}: booking submission`;
        await page.locator('form button[type="submit"]').click();
        await expect(page).toHaveURL(/\/checkout\/confirmation$/, {timeout:30000});
        await expect(page.getByText(/waiting.*confirmation/i).first()).toBeVisible();
        await page.screenshot({path:path.join(output,`${name}-booking-confirmed.png`),fullPage:true});
        await page.goto(`${base}/checkout/payment`);
        await expect(page).toHaveURL(/\/checkout\/confirmation$/);
        // Never write passwords to logs, traces, screenshots, or artifacts.
        stage = `${name}: admin sign-in`;
        await page.goto(`${base}/admin/login`, {waitUntil:'networkidle'});
        await page.locator('input[type="email"]').fill('preview-admin@tinggaljalan.test');
        await page.locator('input[type="password"]').fill(config.admin_password);
        await page.getByRole('button', {name:'Sign in',exact:true}).click();
        await expect(page).toHaveURL(/\/admin\/?$/, {timeout:30000});
        await page.screenshot({path:path.join(output,`${name}-admin-signed-in.png`),fullPage:true});
        stage = `${name}: admin booking search`;
        await page.goto(`${base}/admin/bookings`, {waitUntil:'networkidle'});
        const search = page.getByPlaceholder('Search', {exact:true});
        await search.fill(`${fixture}@tinggaljalan.test`);
        await expect(page.getByText(`Synthetic Preview ${run} ${name}`, {exact:true}).first()).toBeVisible({timeout:15000});
        await page.screenshot({path:path.join(output,`${name}-admin-booking.png`),fullPage:true});
        if (jsErrors) throw new Error('Functional preview pages have JavaScript errors.');
        results.push(`${name}: booking submitted, confirmation displayed, payment route deferred, admin sign-in succeeded, saved booking found in admin.`);
        await context.close();
    }
    await writeFile(path.join(output,'functional-verification.txt'), `Revision: ${revision}\n${results.join('\n')}\nSynthetic booking records retained for review. No external requests permitted.\n`, {mode:0o600});
} catch (error) {
    if (currentPage && !currentPage.isClosed()) {
        await currentPage.screenshot({path:path.join(output,"functional-failure.png"),fullPage:true,mask:[currentPage.locator('input[type="password"]')]}).catch(() => {});
    }
    // Playwright errors may include input values: emit a static failure only.
    console.error(`Functional preview verification failed at ${stage}. Inspect the last completed screenshots.`);
    process.exitCode = 1;
} finally { await browser.close(); }
