import {chromium,expect} from '@playwright/test';
import {readFile,mkdir,writeFile} from 'node:fs/promises';
const [configFile,revision,out]=process.argv.slice(2);
const config=JSON.parse(await readFile(configFile,'utf8'));
const base='https://preview.tinggaljalan.com';
await mkdir(out,{recursive:true});
const browser=await chromium.launch();
const results=[];
try {
 for(const [profile,viewport] of [['desktop',{width:1440,height:1000}],['mobile',{width:390,height:844}]]) {
  for(let sample=0;sample<3;sample++) {
   const context=await browser.newContext({baseURL:base,httpCredentials:{username:'reviewer',password:config.review_password,origin:base},viewport,reducedMotion:'reduce'});
   expect((await (await context.request.get('/up')).json()).revision).toBe(revision);
   const page=await context.newPage();
   const requests=[];
   page.on('response',async r=>{if(r.request().resourceType()==='image') requests.push({url:new URL(r.url()).pathname,status:r.status(),cacheControl:(await r.allHeaders())['cache-control']});});
   let release;const gate=new Promise(r=>release=r);
   await page.route('**/build/assets/HomePage-*.js',async route=>{await gate;await route.continue();});
   await page.addInitScript(()=>{
    window.__handoff={frames:[],started:null};
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
    release();
    await page.waitForFunction(()=>window.__handoff.ready!==undefined||window.__handoff.timeout,{},{timeout:30000});
    const handoff=await page.evaluate(()=>window.__handoff);
    const first=handoff.frames[0];const last=handoff.frames.at(-1);
    const result={profile,sample,initial,first,last,gapMs:handoff.ready,timeout:!!handoff.timeout,requests};
    results.push(result);
    console.log('MEDIA_HANDOFF '+JSON.stringify(result));
   } finally {release();await context.close();}
  }
 }
 await writeFile(out+'/media-handoff.json',JSON.stringify({revision,results},null,2));
} finally {await browser.close();}
