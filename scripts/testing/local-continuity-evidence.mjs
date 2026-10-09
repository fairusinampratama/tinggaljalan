import { chromium } from '@playwright/test';
import { spawn } from 'node:child_process';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import path from 'node:path';
const root=process.env.GITHUB_WORKSPACE;
const output=process.env.RUNNER_TEMP+'/continuity-review';
await mkdir(output,{recursive:true});
const browser=await chromium.launch();
const results=[];
for(const [name,port] of [['before',4173],['after',4174]]) {
 const cwd=root+'/'+name;
 const server=spawn('php',['artisan','serve','--host=127.0.0.1',`--port=${port}`,'--no-reload'],{cwd,env:{...process.env,APP_ENV:'testing',APP_URL:`http://127.0.0.1:${port}`,CACHE_STORE:'array',DB_CONNECTION:'sqlite',DB_DATABASE:cwd+'/storage/framework/testing/browser.sqlite',MAIL_MAILER:'array',QUEUE_CONNECTION:'sync',SESSION_DRIVER:'database'},stdio:'ignore'});
 try {
  const base=`http://127.0.0.1:${port}`;
  for(let i=0;i<80;i++){try{if((await fetch(base+'/up')).ok)break;}catch{}await new Promise(r=>setTimeout(r,250));}
  const ctx=await browser.newContext({baseURL:base,viewport:{width:393,height:851},isMobile:true,reducedMotion:'reduce'});
  const page=await ctx.newPage();
  page.on('console',msg=>{if(msg.type()==='error')console.log('TJ_CONSOLE:'+name+':'+msg.text());});
  page.on('pageerror',error=>console.log('TJ_ERROR:'+name+':'+error.message));
  await page.addInitScript(()=>{window.__stageImages=[];new MutationObserver(()=>{const stage=document.querySelector('[data-initial-stage]');if(stage)window.__stageImages=[...stage.querySelectorAll('nav img,section[id] img')].slice(0,5).map(i=>({src:i.src,current:i.currentSrc,complete:i.complete,width:i.naturalWidth}));}).observe(document,{subtree:true,childList:true,attributes:true});});
  await page.route('https://assets.zyrosite.com/**',route=>route.fulfill({path:cwd+'/public/images/logo-tj.png',contentType:'image/png'}));
  await page.route('**/routes/bromo-sunrise*',async route=>{
   const r=await route.fetch();const body=await r.text();console.log('TJ_PAYLOAD:'+name+':'+body.slice(body.indexOf('data-page')-40,body.indexOf('data-page')+180));const html=body.replace(/(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/,(_,a,json,b)=>{const p=JSON.parse(json);p.props.route.gallery=Array.from({length:7},(_,i)=>p.props.route.image+'?photo='+i);p.props.route.why={us:'**Best For** Nature lovers, adventure seekers and photographers.'};return a+JSON.stringify(p).replace(/</g,'\\u003c')+b;});
   await route.fulfill({response:r,body:html});
  });
  await page.goto('/routes/bromo-sunrise',{waitUntil:'networkidle'});
  await page.waitForFunction(()=>!document.querySelector('.server-seo-content'));
  const dims=await page.evaluate(()=>({viewport:innerWidth,width:document.documentElement.scrollWidth,x:scrollX}));
  results.push({name,...dims}); console.log("TJ_DIMENSIONS:"+JSON.stringify({name,...dims}));
  await page.evaluate(()=>scrollTo({left:0,top:0,behavior:'instant'}));
  await page.screenshot({path:output+'/'+name+'-package.jpg',fullPage:true,type:'jpeg',quality:65});
  let release;const gate=new Promise(r=>release=r);
  await page.route('**/build/assets/HomePage-*.js',async route=>{await gate;await route.continue();});
  await page.goto('/',{waitUntil:'commit'});
  await page.waitForFunction(()=>document.querySelector('.server-seo-content #home'));
  await page.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.querySelectorAll('nav img,#home img')].filter(i=>i.loading!=='lazy').map(i=>i.decode().catch(()=>{})));});
  await page.screenshot({path:output+'/'+name+'-initial.jpg',type:'jpeg',quality:65});
  release();
  try{await page.waitForFunction(()=>!document.querySelector('.server-seo-content'),null,{timeout:18000});}catch(error){console.log('TJ_HANDOFF:'+name+':'+JSON.stringify(await page.evaluate(()=>({images:window.__stageImages,stage:!!document.querySelector('[data-initial-stage]'),html:document.querySelector('#app')?.innerHTML.slice(-800)}))));}
  await page.evaluate(async()=>{await document.fonts.ready;await Promise.all([document.querySelector('nav img'),document.querySelector('#home img')].filter(Boolean).map(i=>i.decode().catch(()=>{})));});
  await page.screenshot({path:output+'/'+name+'-ready.jpg',type:'jpeg',quality:65});
  await ctx.close();
 } finally {server.kill('SIGTERM');}
}
await browser.close();
console.log('TJ_DIMENSIONS:'+JSON.stringify(results));
await writeFile(output+'/dimensions.json',JSON.stringify(results,null,2));
for(const name of ['before-package','after-package','before-initial','after-initial','before-ready','after-ready']) {
 const data=(await readFile(output+'/'+name+'.jpg')).toString('base64');
 for(let i=0;i<data.length;i+=3000)console.log('TJ_IMAGE:'+name+':'+data.slice(i,i+3000));
}
