import { expect, test } from '@playwright/test';
import { writeFile } from 'node:fs/promises';
test('package cards share their surface and remain readable through the full page', async ({ page }, testInfo) => {
  await page.goto('/routes/bromo-sunrise');
  await expect(page.locator('.server-seo-content')).toHaveCount(0);
  const consent=page.getByTestId('consent-decline'); if(await consent.isVisible()) await consent.click();
  const cards=page.locator('[data-route-content-card]');
  expect(await cards.count()).toBeGreaterThan(6);
  const report=await cards.evaluateAll(els=>els.map(el=>{
    const s=getComputedStyle(el),r=el.getBoundingClientRect();
    return {heading:el.querySelector('h2,h3')?.textContent.trim(),style:[s.backgroundColor,s.borderRadius,s.borderTopWidth,s.borderTopColor,s.boxShadow,s.paddingTop,s.paddingRight],width:r.width,height:r.height};
  }));
  for(const card of report){expect(card.style).toEqual(report[0].style);expect(card.width).toBeGreaterThan(200);}
  await page.screenshot({path:testInfo.outputPath('all-package-cards.png'),fullPage:true});
  for(const [i,fraction] of [0,.33,.67,1].entries()){
    await page.evaluate(f=>scrollTo({top:f*(document.documentElement.scrollHeight-innerHeight),behavior:'instant'}),fraction);
    expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1)).toBe(true);
    await page.screenshot({path:testInfo.outputPath('cards-scroll-'+i+'.png')});
  }
  await writeFile(testInfo.outputPath('card-measurements.json'),JSON.stringify(report,null,2));
});
