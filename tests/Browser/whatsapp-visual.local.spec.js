import { test, expect } from '@playwright/test';
import fs from 'node:fs/promises';
const output='whatsapp-cta-review';
test('before and after visual review on identical synthetic content',async({page})=>{
 test.setTimeout(180000);
 page.setDefaultTimeout(15000);
 await fs.mkdir(output,{recursive:true});
 await page.route('https://www.googletagmanager.com/**',r=>r.fulfill({contentType:'application/javascript',body:''}));
 for(const [name,width,height,path] of [['home-mobile',390,844,'/'],['home-desktop',1440,1000,'/'],['route-mobile',390,844,'/routes/bromo-sunrise'],['route-desktop',1440,1000,'/routes/bromo-sunrise'],['about-mobile',390,844,'/about-us'],['home-tablet',768,1024,'/'],['route-narrow',320,740,'/routes/bromo-sunrise']]){
  await page.setViewportSize({width,height});
  for(const [state,port] of [['before',4174],['after',4173]]){
   await page.goto(`http://127.0.0.1:${port}${path}`);
   await expect(page.locator('.server-seo-content')).toHaveCount(0);
   const decline=page.getByTestId('consent-decline');if(await decline.isVisible())await decline.click();
   await page.evaluate(()=>document.fonts.ready);
   await expect.poll(()=>page.locator('main > nav img').evaluate(el=>el.complete && el.naturalWidth>0)).toBe(true);
   console.log(state,name);
   await page.screenshot({path:`${output}/${state}-${name}.png`});
  }
 }
 await page.setViewportSize({width:390,height:844});
 await page.goto('http://127.0.0.1:4173/routes/bromo-sunrise');
 await expect(page.locator('.server-seo-content')).toHaveCount(0);
 await page.getByRole('button',{name:'Cookie settings',exact:true}).click();
 await expect(page.locator('[data-mobile-contact-bar]')).toBeHidden();
 await page.screenshot({path:`${output}/after-route-consent.png`});
 await page.getByTestId('consent-decline').click();
 await page.getByRole('button',{name:'Open menu'}).click();
 await expect(page.locator('[data-mobile-contact-bar]')).toBeHidden();
 await page.screenshot({path:`${output}/after-route-menu.png`});
 await page.getByRole('button',{name:'Close menu'}).click();
 await page.getByTestId('itinerary-timeline').scrollIntoViewIfNeeded();
 await page.screenshot({path:`${output}/after-route-scrolled.png`});
 const info=await page.locator('[data-whatsapp-placement]').evaluateAll(es=>es.map(e=>({placement:e.dataset.whatsappPlacement,rect:e.getBoundingClientRect().toJSON(),visibility:getComputedStyle(e).visibility,display:getComputedStyle(e).display})));
 await fs.writeFile(`${output}/local-dom-review.json`,JSON.stringify(info,null,2));
});
