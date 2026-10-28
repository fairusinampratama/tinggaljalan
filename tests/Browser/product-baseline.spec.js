import {test,expect} from '@playwright/test';
 test('existing Bromo itinerary and gallery baseline',async({page},testInfo)=>{
 await page.goto('/routes/bromo-sunrise');
 await expect(page.locator('.server-seo-content')).toHaveCount(0);
 await page.screenshot({path:testInfo.outputPath('baseline.png'),fullPage:true});
 });
