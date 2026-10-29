import { chromium, expect } from '@playwright/test';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
const [file, revision, output] = process.argv.slice(2);
if (!file || revision !== '98cc58bcdf404d861a1b4fd9c42f427cbae76935' || !output) throw new Error('Unexpected review revision.');
const config = JSON.parse(await readFile(file, 'utf8'));
const base = 'https://preview.tinggaljalan.com';
await mkdir(output, {recursive:true, mode:0o700});
const browser = await chromium.launch();
const results = [];
let stage = 'initialization';
try {
  for (const [name, viewport, isMobile] of [['desktop',{width:1440,height:1000},false],['mobile',{width:390,height:844},true]]) {
    const context = await browser.newContext({viewport,isMobile,httpCredentials:{username:'reviewer',password:config.review_password,origin:base}});
    await context.route('**/*', route => new URL(route.request().url()).origin === base ? route.continue() : route.abort());
    const health = await (await context.request.get(base+'/up')).json();
    if (health.status !== 'up' || health.revision !== revision) throw new Error('Wrong preview revision.');
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', () => errors.push('pageerror'));
    stage = name+': admin login';
    await page.goto(base+'/admin/login', {waitUntil:'networkidle'});
    await page.locator('input[type="email"]').fill('preview-admin@tinggaljalan.test');
    await page.locator('input[type="password"]').fill(config.admin_password);
    await page.getByRole('button',{name:/^(Sign in|Masuk)$/}).click();
    await expect(page).toHaveURL(/\/admin\/?$/, {timeout:30000});
    stage = name+': saved gallery';
    await page.goto(base+'/admin/tour-packages', {waitUntil:'networkidle'});
    const row = page.getByRole('row').filter({hasText:/Bromo/i}).first();
    const link = row.locator('a[href$="/edit"]').first();
    const edit = new URL(await link.getAttribute('href'),base);
    edit.searchParams.set('tour-package-step','form.media-logistics::data::wizard-step');
    await page.goto(edit.href,{waitUntil:'networkidle'});
    const gallery = page.getByRole('group',{name:'Gallery images',exact:true});
    const items = gallery.locator('.filepond--item');
    await expect(items.first()).toBeVisible();
    const count = await items.count();
    if (!count) throw new Error('No saved gallery photographs.');
    const references = await gallery.locator('a').evaluateAll(es=>es.map(e=>e.getAttribute('href')));
    for (let visit=0;visit<2;visit++) {
      await expect(gallery.locator('[data-filepond-item-state="idle"]')).toHaveCount(count,{timeout:30000});
      for (const item of await items.all()) {
        await expect(item.getByRole('button',{name:/^(Remove|Hapus)$/})).toBeEnabled();
        await expect(item.getByRole('button',{name:'edit',exact:true})).toBeEnabled();
        await expect.poll(()=>item.evaluate(e=>(e.querySelector('canvas')?.getBoundingClientRect().height??0)/Math.min(120,e.getBoundingClientRect().width/2))).toBeGreaterThan(1);
        await expect.poll(async()=>{const r=await item.boundingBox();return r.height/r.width;}).toBeCloseTo(.75,1);
      }
      if(visit===0) await page.reload({waitUntil:'networkidle'});
    }
    expect(await gallery.locator('a').evaluateAll(es=>es.map(e=>e.getAttribute('href')))).toEqual(references);
    await gallery.screenshot({path:path.join(output,name+'-gallery.png')});
    await page.screenshot({path:path.join(output,name+'-gallery-page.png'),fullPage:true});
    stage = name+': editor';
    await items.first().getByRole('button',{name:'edit',exact:true}).click();
    const editor = page.locator('.fi-fo-file-upload-editor:visible');
    await expect(editor).toBeVisible();
    await expect.poll(()=>editor.locator('.cropper-canvas img').first().evaluate(i=>i.complete&&i.naturalWidth>0)).toBe(true);
    await page.screenshot({path:path.join(output,name+'-gallery-editor.png')});
    await editor.getByRole('button',{name:/^(Cancel|Batal)$/}).click();
    await expect(editor).toHaveCount(0);
    expect(await gallery.locator('a').evaluateAll(es=>es.map(e=>e.getAttribute('href')))).toEqual(references);
    if(errors.length) throw new Error('Admin JavaScript error.');
    results.push({viewport:name,revision,photos:count,reopened:true,readablePreviews:true,landscapeTiles:true,controlsEnabled:true,editorOpenedAndCancelled:true,referencesUnchanged:true,noSaveOrRemoval:true});
    await context.close();
  }
  await writeFile(path.join(output,'admin-gallery-verification.json'),JSON.stringify(results,null,2),{mode:0o600});
} catch {
  console.error('Read-only admin gallery review failed at '+stage+'. Credentials are not logged.');
  process.exitCode=1;
} finally { await browser.close(); }
