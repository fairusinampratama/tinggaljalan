import { chromium, expect } from '@playwright/test';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import path from 'node:path';

// Read-only public-page review. No submissions, local server, database writes,
// deployment, authentication changes or production interactions.
const [configFile, revision, output] = process.argv.slice(2);
if (!/^[a-f0-9]{40}$/.test(revision ?? '') || !output) throw new Error('Expected exact revision and output.');
const config = JSON.parse(await readFile(configFile, 'utf8'));
const base = 'https://preview.tinggaljalan.com';
const credentials = { username: 'reviewer', password: config.review_password, origin: base };
await mkdir(output, { recursive: true });
const results = [];
const measurements = [];
const browser = await chromium.launch();
async function fonts(page) {
  await page.evaluate(async () => {
    const faces = new Set([...document.querySelectorAll('h2,h3,p,label,select,a')].map(el => {
      const s = getComputedStyle(el); return `${s.fontWeight} ${s.fontSize} ${s.fontFamily}`;
    }));
    await Promise.all([...faces].map(face => document.fonts.load(face)));
    await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
  });
}
try {
  for (const [profile, viewport, mobile] of [
    ['desktop', {width:1440,height:1000}, false], ['mobile',{width:390,height:844},true],
  ]) {
    const context = await browser.newContext({ baseURL: base, httpCredentials: credentials, viewport, isMobile: mobile, reducedMotion: 'reduce' });
    const health = await (await context.request.get('/up')).json();
    if (health.revision !== revision) throw new Error('Runtime revision mismatch.');
    for (const locale of ['us','id','cn']) {
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      let release;
      const gate = new Promise(resolve => { release = resolve; });
      await page.route('**/build/assets/HomePage-*.js', async route => { await gate; await route.continue(); });
      await page.addInitScript(() => {
        window.__firstCopy = { incomplete:false, ready:null };
        new MutationObserver(() => {
          const payload = document.querySelector('script[data-page="app"]');
          if (payload && !window.__expectedCopy) {
            try { window.__expectedCopy = JSON.parse(payload.textContent).props.translations.searchTitle; }
            catch { /* The streamed JSON element is not complete yet. */ }
          }
          if (!document.querySelector('.server-seo-content') && document.querySelector('#home')) {
            if (!document.querySelector('#home').textContent.includes(window.__expectedCopy)) window.__firstCopy.incomplete = true;
            window.__firstCopy.ready ??= performance.now();
          }
        }).observe(document,{childList:true,subtree:true});
      });
      try {
        await page.goto(`/?lang=${locale}`, {waitUntil:'commit'});
        await expect(page.locator('.server-seo-content')).toBeVisible({timeout:30000});
        await fonts(page);
        const initial = await page.locator('#home').boundingBox();
        const logo = await page.locator('.server-seo-content nav img').getAttribute('src');
        await page.screenshot({path:path.join(output,`${profile}-${locale}-initial.png`)});
        release();
        await expect(page.locator('.server-seo-content')).toHaveCount(0,{timeout:30000});
        const expected = await page.evaluate(() => window.__expectedCopy);
        await expect(page.locator('#home')).toContainText(expected);
        await fonts(page);
        const final = await page.locator('#home').boundingBox();
        expect(Math.abs(initial.height-final.height)).toBeLessThan(8);
        expect(await page.locator('nav img').first().getAttribute('src')).toBe(logo);
        expect((await page.evaluate(() => window.__firstCopy)).incomplete).toBe(false);
        expect(errors).toEqual([]);
        await page.screenshot({path:path.join(output,`${profile}-${locale}-react.png`)});
        results.push({profile,locale,heroHeight:[initial.height,final.height],logo,firstCopyComplete:true});
      } finally { release(); await page.close(); }
    }
    const fragment = await context.newPage();
    let release;
    const gate = new Promise(resolve => { release = resolve; });
    await fragment.route('**/build/assets/HomePage-*.js',async route => {await gate; await route.continue();});
    try {
      await fragment.goto('/#destination',{waitUntil:'commit'});
      await expect(fragment.locator('.server-seo-content')).toBeVisible({timeout:30000});
      await fonts(fragment);
      await expect(fragment.locator('#destination')).toBeInViewport();
      const before = await fragment.locator('#destination').boundingBox();
      await fragment.screenshot({path:path.join(output,`${profile}-fragment-initial.png`)});
      release();
      await expect(fragment.locator('.server-seo-content')).toHaveCount(0,{timeout:30000});
      await fonts(fragment);
      await expect(fragment.locator('#destination')).toBeInViewport();
      const after = await fragment.locator('#destination').boundingBox();
      expect(Math.abs(before.y-after.y)).toBeLessThan(8);
      await fragment.screenshot({path:path.join(output,`${profile}-fragment-react.png`)});
      results.push({profile,fragmentOffset:[before.y,after.y]});
    } finally {release();await fragment.close();}
    const failed = await context.newPage();
    await failed.route('**/build/assets/*.js',route=>route.abort());
    await failed.goto('/',{waitUntil:'networkidle'});
    await expect(failed.locator('.server-seo-content')).toBeVisible();
    await expect(failed.locator('#destination a').first()).toBeVisible();
    await failed.screenshot({path:path.join(output,`${profile}-failed-js.png`)});
    await failed.close();
    await context.close();
    const nojs = await browser.newContext({baseURL:base,httpCredentials:credentials,viewport,javaScriptEnabled:false});
    const readable = await nojs.newPage();
    await readable.goto('/#destination');
    await expect(readable.locator('.server-seo-content')).toBeVisible();
    await expect(readable.locator('#destination h2')).toBeVisible();
    await readable.screenshot({path:path.join(output,`${profile}-no-js.png`)});
    await nojs.close();
    for (let sample=0;sample<3;sample++) {
      const cold = await browser.newContext({baseURL:base,httpCredentials:credentials,viewport,isMobile:mobile,reducedMotion:'reduce'});
      const page = await cold.newPage();
      const session = await cold.newCDPSession(page);
      await session.send('Network.enable');
      await session.send('Network.setCacheDisabled',{cacheDisabled:true});
      await session.send('Network.emulateNetworkConditions',{offline:false,latency:mobile?150:40,downloadThroughput:(mobile?1.6:8)*1000000/8,uploadThroughput:750000});
      await session.send('Emulation.setCPUThrottlingRate',{rate:mobile?4:1});
      await page.addInitScript(() => {
        window.__timings = {lcp:0,cls:0,reactReady:null};
        new PerformanceObserver(list=>{for(const e of list.getEntries()) window.__timings.lcp=e.startTime;}).observe({type:'largest-contentful-paint',buffered:true});
        new PerformanceObserver(list=>{for(const e of list.getEntries()) if(!e.hadRecentInput) window.__timings.cls+=e.value;}).observe({type:'layout-shift',buffered:true});
        new MutationObserver(()=>{if(!document.querySelector('.server-seo-content')&&document.querySelector('#home')) window.__timings.reactReady??=performance.now();}).observe(document,{childList:true,subtree:true});
      });
      await page.goto('/',{waitUntil:'domcontentloaded'});
      await expect(page.locator('.server-seo-content')).toHaveCount(0,{timeout:60000});
      await expect(page.locator('#home')).toContainText('Find a Trip');
      await page.waitForTimeout(2000);
      measurements.push(await page.evaluate(({profile,sample})=>({profile,sample,...window.__timings,ttfb:performance.getEntriesByType('navigation')[0].responseStart,fcp:performance.getEntriesByName('first-contentful-paint')[0]?.startTime}),{profile,sample}));
      if(sample===0) await page.screenshot({path:path.join(output,`${profile}-throttled-react.png`)});
      await cold.close();
    }
  }
  await writeFile(path.join(output,'loading-verification.json'),JSON.stringify({revision,results,measurements},null,2));
  console.log(`Initial loading review passed: ${results.length} transition checks and ${measurements.length} cold timing samples.`);
} finally { await browser.close(); }
