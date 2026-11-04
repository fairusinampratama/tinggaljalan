import { chromium } from '@playwright/test';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';
const [configPath, revision, output] = process.argv.slice(2);
const config = JSON.parse(await readFile(configPath, 'utf8'));
const base = 'https://preview.tinggaljalan.com';
await mkdir(output, {recursive:true,mode:0o700});
const browser = await chromium.launch();
const results=[];
try {
 const waitContext=await browser.newContext({httpCredentials:{username:'reviewer',password:config.review_password,origin:base}});
 let ready=false;
 for(let i=0;i<120;i++) {
  try { const r=await waitContext.request.get(base+'/up?cta_review='+Date.now(),{timeout:15000}); const h=await r.json(); if(r.ok() && h.status==='up' && h.revision===revision){ready=true;break;} } catch {}
  await new Promise(resolve=>setTimeout(resolve,15000));
 }
 await waitContext.close();
 if(!ready)throw new Error('Reviewed application revision did not become active within the review window.');
 const anonymous=await browser.newContext();
 for(const p of ['/','/up','/images/brand/whatsapp-glyph-white.svg']) {
  const r=await anonymous.request.get(base+p);if(r.status()!==401)throw new Error('Preview authentication protection failed.');
 }
 await anonymous.close();
 for(const [name,viewport,isMobile] of [['desktop',{width:1440,height:1000},false],['mobile',{width:390,height:844},true],['tablet',{width:768,height:1024},true]]) {
  const context=await browser.newContext({viewport,isMobile,deviceScaleFactor:1,httpCredentials:{username:'reviewer',password:config.review_password,origin:base}});
  const page=await context.newPage();
  const errors=[];page.on('pageerror',()=>errors.push('pageerror'));
  const floating=()=>page.locator('[data-whatsapp-placement="floating"]');
  for(const [label,path] of [['home','/'],['route','/routes/BROMO'],['about','/about-us']]) {
   const response=await page.goto(base+path,{waitUntil:'networkidle'});
   if(!response.ok() || !/noindex/i.test(response.headers()['x-robots-tag']??''))throw new Error('Hosted public page or noindex verification failed: '+name+' '+label);
   await page.locator('.server-seo-content').waitFor({state:'detached'});
   const f=floating();
   const expectedVisible=label!=='route' || viewport.width>=1024;
   if(await f.isVisible()!==expectedVisible)throw new Error('Floating CTA visibility mismatch: '+name+' '+label);
   const footer=page.locator('[data-whatsapp-placement="footer"]');
   if(await f.getAttribute('href')!==await footer.getAttribute('href'))throw new Error('Floating destination differs from existing footer destination.');
   if(!/^https:\/\/(?:[^/]+\.)?(?:wa\.me|whatsapp\.com)\//i.test(await f.getAttribute('href')))throw new Error('Unexpected WhatsApp destination type.');
   if(expectedVisible) {
    const r=await f.boundingBox();const icon=await f.locator('img').boundingBox();
    const size=viewport.width<640?56:48,logo=viewport.width<640?28:24;
    if(r.height!==size || icon.width!==logo || (viewport.width<640 && r.width!==56))throw new Error('Hosted CTA dimension mismatch.');
    if(r.x<0 || r.y<0 || r.x+r.width>viewport.width+1 || r.y+r.height>viewport.height+1)throw new Error('Floating CTA outside viewport.');
    await f.locator('img').evaluate(el=>el.decode());
   }
   const width=await page.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth}));
   if(width.document>width.viewport+2)throw new Error('Hosted horizontal overflow: '+name+' '+label);
   const analytics=await page.locator('script[data-google-analytics]').count()?await page.locator('script[data-google-analytics]').getAttribute('data-google-analytics'):null;
   if(analytics || await page.locator('script[src*="googletagmanager.com"]').count())throw new Error('Live Google tracking unexpectedly enabled on preview.');
   const hrefDigest=createHash('sha256').update(await f.getAttribute('href')).digest('hex');
   results.push({viewport:name,page:label,width,ctaVisible:expectedVisible,linkDigest:hrefDigest,accessibleName:await f.getAttribute('aria-label'),trackingDisabled:true});
   await page.screenshot({path:output+'/'+name+'-'+label+'-top.png'});
   if(label==='route' && viewport.width<1024) {
    const contact=page.locator('[data-whatsapp-placement="route-mobile"]');
    if(!await contact.isVisible() || (await contact.boundingBox()).height<48)throw new Error('Mobile route contact action unavailable.');
    await page.getByRole('button',{name:'Open menu',exact:true}).click();
    if(await contact.isVisible())throw new Error('Route bar overlaps mobile menu.');
    await page.screenshot({path:output+'/'+name+'-route-menu.png'});
    await page.getByRole('button',{name:'Close menu',exact:true}).click();
    await page.getByTestId('itinerary-timeline').scrollIntoViewIfNeeded();
    await page.screenshot({path:output+'/'+name+'-route-scrolled.png'});
    if(!await contact.isVisible())throw new Error('Route contact action lost after scroll.');
   }
   await page.evaluate(()=>window.scrollTo({top:document.documentElement.scrollHeight,behavior:'instant'}));
   const lastLink=page.locator('footer a[href="#home"]');
   const rect=await lastLink.boundingBox();
   const persistent=label==='route' && viewport.width<1024?page.locator('[data-mobile-contact-bar]'):f;
   const persistentRect=await persistent.boundingBox();
   if(rect.y+rect.height>persistentRect.y-1)throw new Error('Footer navigation covered by persistent contact action.');
   if(expectedVisible) {
    await lastLink.focus();await page.keyboard.press('Tab');
    if(!await f.evaluate(el=>el===document.activeElement && getComputedStyle(el).outlineStyle==='solid'))throw new Error('Hosted keyboard focus is not visible.');
   }
   await page.screenshot({path:output+'/'+name+'-'+label+'-footer.png'});
  }
  for(const [locale,label] of [['id','Chat melalui WhatsApp'],['cn','通过 WhatsApp 咨询']]) {
   await page.goto(base+'/language/'+locale,{waitUntil:'networkidle'});
   await page.locator('.server-seo-content').waitFor({state:'detached'});
   if(await floating().getAttribute('aria-label')!==label)throw new Error('Hosted localized contact name mismatch.');
  }
  if(errors.length)throw new Error('Hosted page reported application errors.');
  await context.close();
 }
 await writeFile(output+'/whatsapp-hosted-results.json',JSON.stringify({revision,passed:true,results,consentNote:'Preview live tracking remains disabled; consent-panel coexistence was validated in isolated browser CI.'},null,2));
 console.log('Reviewed revision, authenticated/noindex protection, CTA dimensions, links, keyboard, localization, footer clearance, responsive layouts and disabled tracking passed.');
} finally {await browser.close();}
