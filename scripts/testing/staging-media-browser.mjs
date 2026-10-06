import { chromium, expect } from '@playwright/test';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
const [file, revision, output] = process.argv.slice(2);
if (!file || !/^[a-f0-9]{40}$/.test(revision ?? '') || !output) throw new Error('Invalid media check arguments.');
const config = JSON.parse(await readFile(file, 'utf8'));
const base = 'https://preview.tinggaljalan.com';
await mkdir(output, {recursive:true,mode:0o700});
const browser = await chromium.launch();
const results = [];
let stage = 'runtime';
try {
    for (const [name, viewport] of [['desktop',{width:1440,height:1000}],['mobile',{width:390,height:844}]]) {
        const context = await browser.newContext({viewport,httpCredentials:{username:'reviewer',password:config.review_password,origin:base}});
        const health = await (await context.request.get(`${base}/up?revision=${revision}`)).json();
        if (health.status !== 'up' || health.revision !== revision) throw new Error('Unexpected runtime.');
        await context.route('**/*', route => new URL(route.request().url()).origin === base ? route.continue() : route.abort());
        const page = await context.newPage();
        const routes = ['/', '/routes', '/news', '/about-us'];
        let checked = 0;
        let variants = 0;
        for (const route of routes) {
            stage = `${name}: media rendering`;
            const response = await page.goto(`${base}${route}`,{waitUntil:'networkidle'});
            if (!response?.ok()) throw new Error('Page failed.');
            await page.locator('img').evaluateAll(images => { for (const image of images) image.loading = 'eager'; });
            await expect.poll(async () => page.locator('img').evaluateAll(images => images.filter(image => {
                const url = new URL(image.currentSrc || image.src, location.origin);
                return url.origin === location.origin && (!image.complete || image.naturalWidth === 0);
            }).length),{timeout:30000}).toBe(0);
            const images = await page.locator('img').evaluateAll(images => images.map(image => image.currentSrc || image.src));
            if (images.some(url => /https?:\/\/(?:www\.)?tinggaljalan\.com\//i.test(url))) throw new Error('Preview references production media.');
            checked += images.filter(url => url.startsWith(base)).length;
            variants += images.filter(url => url.includes('/storage/generated/')).length;
            if (route === '/about-us') await page.screenshot({path:path.join(output,`${name}-about-media.png`),fullPage:true});
        }
        if (!checked || !variants) throw new Error('No local images or responsive upload variants rendered.');
        results.push(`${name}: ${checked} local rendered images checked; ${variants} responsive upload variants selected; no broken local images or production media references.`);
        await context.close();
    }
    await writeFile(path.join(output,'media-verification.txt'),`Revision: ${revision}\n${results.join('\n')}\n`,{mode:0o600});
} catch {
    console.error(`Preview media verification failed at ${stage}; no private values logged.`);
    process.exitCode = 1;
} finally { await browser.close(); }
