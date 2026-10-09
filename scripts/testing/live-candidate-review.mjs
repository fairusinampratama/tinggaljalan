import {chromium,expect} from '@playwright/test';
import {readFile,mkdir,writeFile} from 'node:fs/promises';
const [configFile,revision,out]=process.argv.slice(2);const config=JSON.parse(await readFile(configFile,'utf8'));const base='https://preview.tinggaljalan.com';
await mkdir(out,{recursive:true});const browser=await chromium.launch();
const ctx=await browser.newContext({baseURL:base,httpCredentials:{username:'reviewer',password:config.review_password,origin:base},viewport:{width:393,height:851},isMobile:true,reducedMotion:'reduce'});
try{expect((await(await ctx.request.get('/up')).json()).revision).toBe(revision);
const page=await ctx.newPage();const responses=[];page.on('response',r=>{if(r.request().resourceType()==='image')responses.push({path:new URL(r.url()).pathname,status:r.status()})});
await page.goto('/routes/Tumpaksewu',{waitUntil:'networkidle'});await expect(page.locator('.server-seo-content')).toHaveCount(0);
const img=page.getByRole('button',{name:'Open full-screen gallery',exact:true}).locator('img');
const before=await img.evaluate(e=>({src:e.currentSrc,complete:e.complete,naturalWidth:e.naturalWidth,style:{opacity:getComputedStyle(e).opacity,visibility:getComputedStyle(e).visibility},rect:e.getBoundingClientRect().toJSON()}));
console.log('GALLERY_BEFORE '+JSON.stringify({before,responses}));
await expect(img).toBeVisible();await expect.poll(()=>img.evaluate(e=>e.complete&&e.naturalWidth>0),{timeout:30000}).toBe(true);await img.evaluate(e=>e.decode());
const bytes=await page.screenshot({type:'jpeg',quality:75});await writeFile(out+'/mobile-package-loaded.jpg',bytes);const b=bytes.toString('base64');for(let i=0;i<b.length;i+=6000)console.log('SHOT mobile-package-loaded '+i+' '+b.slice(i,i+6000));
console.log('GALLERY_AFTER '+JSON.stringify(await img.evaluate(e=>({src:e.currentSrc,complete:e.complete,naturalWidth:e.naturalWidth}))));
}finally{await ctx.close();await browser.close();}