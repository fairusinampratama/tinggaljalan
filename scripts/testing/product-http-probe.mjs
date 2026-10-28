import { request } from '@playwright/test';
import {readFile,writeFile,mkdir} from 'node:fs/promises';
const [file,revision,output]=process.argv.slice(2);
const config=JSON.parse(await readFile(file,'utf8')), base='https://preview.tinggaljalan.com';
await mkdir(output,{recursive:true});
const result=[];
for(const [name,send,userAgent,route] of [
 ['visitor','unauthorized','Mozilla/5.0','/news/Bromo-wildfire'],
 ['bot-challenge','unauthorized','Googlebot','/news/Bromo-wildfire'],
 ['bot-preauthenticated','always','Googlebot','/news/Bromo-wildfire'],
 ['bot-health','always','Googlebot','/up'],
]) {
 const c=await request.newContext({httpCredentials:{username:'reviewer',password:config.review_password,origin:base,send},userAgent});
 const r=await c.get(base+route),body=await r.text(),h=r.headers();
 result.push({name,route,status:r.status(),title:body.match(/<title[^>]*>([\s\S]*?)<\/title>/i)?.[1]?.trim(),server:h.server,type:h['content-type'],robots:h['x-robots-tag'],authenticationChallenge:!!h['www-authenticate'],bodyPrefix:r.status()===403?body.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim().slice(0,300):undefined});
 await c.dispose();
}
await writeFile(output+'/http-diagnostic.json',JSON.stringify({revision,result},null,2));
console.log(JSON.stringify(result,null,2));
