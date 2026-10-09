import {chromium,expect} from '@playwright/test';
import {readFile,mkdir,writeFile} from 'node:fs/promises';
const [configFile,revision,out]=process.argv.slice(2);
const config=JSON.parse(await readFile(configFile,'utf8'));
const base='https://preview.tinggaljalan.com';
const access={baseURL:base,httpCredentials:{username:'reviewer',password:config.review_password,origin:base}};
await mkdir(out,{recursive:true});
const browser=await chromium.launch();
const results=[];
async function shot(page,name){const bytes=await page.screenshot({type:'jpeg',quality:65});await writeFile(out+'/'+name+'.jpg',bytes); const b=bytes.toString('base64');for(let i=0;i<b.length;i+=6000) console.log('SHOT '+name+' '+i+' '+b.slice(i,i+6000));}
try {
 for(const [profile,viewport] of [['desktop',{width:1440,height:900}],['mobile',{width:393,height:851}]]) {
  for(let sample=0;sample<0;sample++) {
   const context=await browser.newContext({...access,viewport,reducedMotion:'reduce'});
   expect((await (await context.request.get('/up')).json()).revision).toBe(revision);
   const page=await context.newPage();
   const requests=[];
   page.on('response',async r=>{if(r.request().resourceType()==='image') requests.push({url:new URL(r.url()).pathname,status:r.status(),cacheControl:(await r.allHeaders())['cache-control']});});
   let release;const gate=new Promise(r=>release=r);
   await page.route('**/build/assets/HomePage-*.js',async route=>{await gate;await route.continue();});
   await page.addInitScript(()=>{
    window.__handoff={frames:[],started:null}; window.__vitals={cls:0}; new PerformanceObserver(l=>{for(const e of l.getEntries())if(!e.hadRecentInput)window.__vitals.cls+=e.value}).observe({type:'layout-shift',buffered:true}); new PerformanceObserver(l=>{window.__vitals.lcp=l.getEntries().at(-1)?.startTime}).observe({type:'largest-contentful-paint',buffered:true});
    const image=el=>el?{src:el.currentSrc,complete:el.complete,width:el.naturalWidth}:null;
    function frame(){
     const initial=!!document.querySelector('.server-seo-content');
     if(!initial&&document.querySelector('#home')){
      const h=window.__handoff;h.started??=performance.now();
      const logo=image(document.querySelector('nav img'));const hero=image(document.querySelector('#home img'));
      h.frames.push({elapsed:performance.now()-h.started,logo,hero});
      if(logo?.width&&hero?.width&&logo.complete&&hero.complete){h.ready=performance.now()-h.started;return;}
      if(performance.now()-h.started>15000){h.timeout=true;return;}
     }
     requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
   });
   try {
    await page.goto('/',{waitUntil:'commit'});
    await expect(page.locator('.server-seo-content')).toBeVisible();
    await page.waitForFunction(()=>[document.querySelector('nav img'),document.querySelector('#home img')].every(el=>el?.complete&&el.naturalWidth));
    const initial=await page.evaluate(()=>[document.querySelector('nav img'),document.querySelector('#home img')].map(el=>({src:el.currentSrc,width:el.naturalWidth})));
    if(sample===0) await shot(page,profile+'-initial');
    release();
    await page.waitForFunction(()=>window.__handoff.ready!==undefined||window.__handoff.timeout,{},{timeout:30000});
    const handoff=await page.evaluate(()=>window.__handoff);
    const first=handoff.frames[0];const last=handoff.frames.at(-1);
    expect(handoff.timeout).toBeFalsy(); expect(first.logo.complete&&first.logo.width>0).toBeTruthy();expect(first.hero.complete&&first.hero.width>0).toBeTruthy();
    if(sample===0) await shot(page,profile+'-ready');
    const timing=await page.evaluate(()=>({ttfb:performance.getEntriesByType('navigation')[0].responseStart,fcp:performance.getEntriesByName('first-contentful-paint')[0]?.startTime,...window.__vitals}));
    const result={profile,sample,initial,first,last,gapMs:handoff.ready,reactReadyMs:handoff.started,timing,timeout:!!handoff.timeout,requests};
    results.push(result);
    console.log('MEDIA_HANDOFF '+JSON.stringify(result));
   } finally {release();await context.close();}
  }
 }

 const ctx=await browser.newContext({...access,viewport:{width:393,height:851},isMobile:true});
 const articles=['Bromo-wildfire','trip-ijen-creater','bukit-lawang-jungle-trekking-orangutan-guide'];
 await mkdir(out+'/articles',{recursive:true});
 for(const slug of articles){for(const agent of ['normal','googlebot']){
  const headers=agent==='googlebot'?{'User-Agent':'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'}:{};
  const response=await ctx.request.get('/news/'+slug,{headers});expect(response.ok()).toBeTruthy();
  await writeFile(out+'/articles/'+slug+'-'+agent+'.html',await response.text());
 }}
 const page=await ctx.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.route(/\/build\/assets\/(?:us|id|cn)-[^/]+\.js$/,route=>route.abort());
 for(const [lang,title] of [['id','Cari Trip'],['cn','寻找行程'],['us','Find a Trip']]) {
  await page.goto('/?lang='+lang,{waitUntil:'networkidle'});
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  await expect(page.locator('#home')).toContainText(title);
 }
 await page.goto('/',{waitUntil:'networkidle'});
 for(const [label,title] of [['ID','Cari Trip'],['中文','寻找行程'],['EN','Find a Trip']]){
  const button=page.locator('nav').getByRole('button',{name:label,exact:true});
  if(!await button.isVisible())await page.locator('nav').getByRole('button',{name:'Open menu'}).click();
  await button.click();await expect(page.locator('#home')).toContainText(title);
 }
 console.log('PREVIEW_LOCALES complete initial dictionaries and language switching passed');


 await page.goto('/routes',{waitUntil:'networkidle'});
 const card=page.locator('article[role="link"]').filter({hasText:/Tumpak Sewu/i}).first();
 await card.click();await page.waitForURL(/\/routes\/[^/?]+/);await page.waitForLoadState('networkidle');
 const href=new URL(page.url()).pathname;await expect(page.locator('.server-seo-content')).toHaveCount(0);
 const geometry=await page.evaluate(()=>({client:document.documentElement.clientWidth,document:document.documentElement.scrollWidth}));
 expect(geometry.document).toBeLessThanOrEqual(geometry.client+1);
 expect(await page.locator('body').innerText()).not.toContain('**Best For**');
 await page.locator('img[alt*='+JSON.stringify('Tumpak')+']').first().waitFor();
 await page.waitForFunction(()=>Array.from(document.querySelectorAll('img')).filter(e=>e.alt.includes('Tumpak')).some(e=>e.complete&&e.naturalWidth));
 await shot(page,'mobile-package-top');
 const thumbs=page.locator('button[aria-label*="View photo"]');
 const n=await thumbs.count();
 if(n) {await thumbs.last().click();const next=await page.evaluate(()=>({client:document.documentElement.clientWidth,document:document.documentElement.scrollWidth}));expect(next.document).toBeLessThanOrEqual(next.client+1);}
 await page.getByText(/selected package/i).first().scrollIntoViewIfNeeded();
 await shot(page,'mobile-package-booking');
 await page.goto('/#destination',{waitUntil:'networkidle'});await expect(page.locator('.server-seo-content')).toHaveCount(0);
 const fragment=await page.locator('#destination').evaluate(e=>({top:e.getBoundingClientRect().top,scrollY:window.scrollY}));
 expect(Math.abs(fragment.top)).toBeLessThan(150);await shot(page,'mobile-destination');
 expect(errors).toEqual([]);
 console.log('LIVE_EXTRA '+JSON.stringify({href,geometry,thumbnails:n,fragment,errors}));
 await ctx.close();
 const slow=await browser.newContext({...access,viewport:{width:393,height:851},isMobile:true,reducedMotion:'reduce'});
 const sp=await slow.newPage();const cdp=await slow.newCDPSession(sp);
 await cdp.send('Network.enable');await cdp.send('Network.emulateNetworkConditions',{offline:false,latency:150,downloadThroughput:187500,uploadThroughput:93750});
 let open;const blocked=new Promise(r=>open=r);await sp.route('**/build/assets/*.js',async route=>{await blocked;await route.continue()});
 await sp.goto('/',{waitUntil:'commit'});await expect(sp.locator('.server-seo-content')).toBeVisible();
 await sp.waitForFunction(()=>[document.querySelector('nav img'),document.querySelector('#home img')].every(e=>e?.complete&&e.naturalWidth));
 await shot(sp,'mobile-slow-initial');open();
 await expect(sp.locator('.server-seo-content')).toHaveCount(0,{timeout:45000});
 const ready=await sp.evaluate(()=>({width:document.documentElement.scrollWidth,client:document.documentElement.clientWidth,images:[document.querySelector('nav img'),document.querySelector('#home img')].map(e=>({ready:e?.complete&&e?.naturalWidth>0}))}));
 expect(ready.images.every(e=>e.ready)).toBeTruthy();expect(ready.width).toBeLessThanOrEqual(ready.client+1);await shot(sp,'mobile-slow-ready');
 console.log('SLOW_NETWORK '+JSON.stringify({latencyMs:150,downloadMbps:1.5,ready}));await slow.close();
 await writeFile(out+'/media-handoff.json',JSON.stringify({revision,results},null,2));
} finally {await browser.close();}

