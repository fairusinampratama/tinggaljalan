import { chromium, expect } from '@playwright/test';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import assert from 'node:assert/strict';
import path from 'node:path';

const [configFile, revision, output, run, sshConfig] = process.argv.slice(2);
if (!/^[a-f0-9]{40}$/.test(revision || '') || !/^\d+-\d+$/.test(run || '')) throw new Error('Invalid review identity.');
const config = JSON.parse(await readFile(configFile, 'utf8'));
const base = 'https://preview.tinggaljalan.com';
const helper = await readFile('scripts/testing/news-preview-fixture.php', 'utf8');
const fixtureCall = mode => JSON.parse(execFileSync('ssh', ['-F', sshConfig, 'staging',
  `/opt/alt/php84/usr/bin/php /dev/stdin '${revision}' '${run}' '${mode}'`], {input:helper, encoding:'utf8', timeout:60000}));
const fixture = fixtureCall('inspect');
await mkdir(output, {recursive:true, mode:0o700});
const browser = await chromium.launch();
const credentials = {username:'reviewer', password:config.review_password, origin:base};
let stage = 'runtime'; let currentPage;
const results = [];
try {
  const admin = await browser.newContext({viewport:{width:1440,height:1000},httpCredentials:credentials,reducedMotion:'reduce'});
  const health = await (await admin.request.get(`${base}/up`)).json();
  assert.equal(health.revision, revision);
  const page = await admin.newPage(); currentPage=page;
  page.setDefaultTimeout(30000);
  stage='admin sign-in';
  await page.goto(`${base}/admin/login`,{waitUntil:'networkidle'});
  await page.locator('input[type=email]').fill('preview-admin@tinggaljalan.test');
  await page.locator('input[type=password]').fill(config.admin_password);
  await page.getByRole('button',{name:'Sign in',exact:true}).click();
  await expect(page).toHaveURL(/\/admin\/?$/);
  stage='open synthetic article';
  await page.goto(`${base}/admin/news-articles`,{waitUntil:'networkidle'});
  const search=page.getByPlaceholder('Search',{exact:true});
  if(await search.count()) await search.fill(fixture.title);
  await page.getByRole('row').filter({hasText:fixture.title}).getByRole('link',{name:'Edit',exact:true}).click();
  const editURL=page.url();
  const openContent=async()=>{
    if(!await page.getByText('Section photographs',{exact:true}).first().isVisible()) await page.getByRole('button',{name:'Next',exact:true}).click();
    await expect(page.getByText('Section photographs',{exact:true}).first()).toBeVisible();
  };
  await openContent();
  const photos=[
    ['public/images/hero-bromo.jpg','Synthetic morning light over Mount Bromo','The landscape opens up in the morning light.'],
    ['public/images/routes/unique/tumpak-sewu-daytrip.jpg','Synthetic waterfall in a green valley','A quieter stop along the journey.'],
    ['public/images/gallery-indonesia-green.jpg','Synthetic green hills on the journey',''],
  ];
  // Read-only follow-up to the successful upload/save verification.
  await page.goto(editURL,{waitUntil:'networkidle'}); await openContent();
  for(let i=0;i<photos.length;i++) await expect(page.locator('input[id$=".alt.us"]').nth(i)).toHaveValue(photos[i][1]);
  await page.locator('textarea[id$=".caption.us"]').first().evaluate(e=>e.scrollIntoView({block:'center',behavior:'instant'}));
  await expect(page.locator('.filepond--image-preview:visible').first()).toBeVisible();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('.filepond--file-status-main:visible').filter({hasText:/loading|waiting/i})).toHaveCount(0,{timeout:60000});
  await page.locator('.filepond--image-preview:visible').first().evaluate(e=>e.scrollIntoView({block:'center',behavior:'instant'}));
  await page.screenshot({path:path.join(output,'desktop-admin-photos.png')});
  results.push('Read-only verification of saved photo metadata and fully loaded reopened image previews passed. Original admin uploads were verified in run 39724985689.');
  const stored=fixtureCall('inspect');
  assert.equal(stored.cover_image,fixture.cover_image);
  assert.equal(stored.other_news_sha256,fixture.other_news_sha256);
  assert.deepEqual(stored.sections.map(s=>s.images.length),[2,1]);
  for(const [file,digest] of Object.entries(fixture.media)) assert.equal(stored.media[file],digest,`Existing media changed: ${file}`);
  const uploaded=stored.sections.flatMap(s=>s.images);
  for(const photo of uploaded){
    assert.match(photo.path,/^admin\/news\/sections\/[^/]+\.(jpg|jpeg|png|webp)$/i);
    assert.ok(stored.media['/'+photo.path]);
    for(const width of [480,768,960,1200,1600]) {
      const stem=photo.path.replace(/\.[^.]+$/,'');
      assert.ok(stored.media[`/generated/storage/${stem}-${width}.webp`]);
    }
  }
  await admin.close();
  for(const [name,viewport] of [['desktop',{width:1440,height:1000}],['mobile',{width:390,height:844}]]){
    stage=`${name} public photos`;
    const context=await browser.newContext({viewport,httpCredentials:credentials,reducedMotion:'reduce'});
    await context.route('**/*',r=>new URL(r.request().url()).origin===base?r.continue():r.abort());
    const publicPage=await context.newPage();currentPage=publicPage;
    const target=`${base}/news/${fixture.slug}?lang=cn`;
    const raw=await context.request.get(target);
    assert.ok(raw.ok()); assert.match(raw.headers()['x-robots-tag'],/noindex/);
    const nojs=await browser.newContext({viewport,httpCredentials:credentials,javaScriptEnabled:false});
    const server=await nojs.newPage();await server.goto(target,{waitUntil:'networkidle'});
    const summarize=figures=>figures.map(f=>({section:f.closest('section').id,src:f.querySelector('img').getAttribute('src'),alt:f.querySelector('img').alt,caption:f.querySelector('figcaption')?.textContent||''}));
    const expected=await server.locator('.news-section-photo').evaluateAll(summarize);
    assert.equal(expected.length,3);
    await publicPage.goto(target,{waitUntil:'networkidle'});
    await expect(publicPage.locator('.server-seo-content')).toHaveCount(0);
    assert.deepEqual(await publicPage.locator('.news-section-photo').evaluateAll(summarize),expected);
    assert.equal(await publicPage.locator('.news-section-photo figcaption').count(),2);
    const social=await publicPage.locator('meta[property="og:image"]').getAttribute('content');
    assert.equal(social,await server.locator('meta[property="og:image"]').getAttribute('content'));
    await publicPage.goto(`${base}/news/${fixture.slug}?lang=us`,{waitUntil:'networkidle'});
    await expect(publicPage.locator('.server-seo-content')).toHaveCount(0);
    for(const figure of await publicPage.locator('.news-section-photo').all()){
      await figure.evaluate(e=>e.scrollIntoView({block:'center',behavior:'instant'}));
      await expect.poll(()=>figure.locator('img').evaluate(i=>i.complete&&i.naturalWidth>0),{timeout:30000}).toBe(true);
      assert.equal(await figure.locator('img').getAttribute('loading'),'lazy');
      assert.match(await figure.locator('source').getAttribute('srcset'),/-480.webp 480w/);
    }
    assert.ok(await publicPage.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
    for(const [index,figure] of (await publicPage.locator('.news-section-photo').all()).entries()){
      await figure.evaluate(e=>window.scrollTo({top:e.getBoundingClientRect().top+scrollY-100,behavior:'instant'}));
      await publicPage.screenshot({path:path.join(output,`${name}-photo-${index+1}.png`)});
    }
    await publicPage.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
    await publicPage.screenshot({path:path.join(output,`${name}-article.png`),fullPage:true});
    const legacy='/news/Bromo-wildfire';
    await publicPage.goto(base+legacy,{waitUntil:'networkidle'});
    await expect(publicPage.locator('.server-seo-content')).toHaveCount(0);
    await expect(publicPage.locator('.news-section-photo')).toHaveCount(0);
    await publicPage.screenshot({path:path.join(output,`${name}-legacy.png`)});
    results.push(`${name}: exact runtime, three ordered section photos, optional captions, Chinese fallback, initial HTML/React parity, loaded WebP sources and no overflow passed; legacy article rendered.`);
    await nojs.close();await context.close();
  }
  await writeFile(path.join(output,'news-verification.json'),JSON.stringify({revision,article:`${base}/news/${fixture.slug}`,results,existing_media_preserved:true,existing_news_preserved:true,cover_preserved:true},null,2));
}catch(error){
  if(currentPage&&!currentPage.isClosed())await currentPage.screenshot({path:path.join(output,'news-review-failure.png'),mask:[currentPage.locator('input[type=password]')]}).catch(()=>{});
  console.error(`News preview verification failed at ${stage}. No credentials logged.`);
  process.exitCode=1;
}finally{await browser.close();}
