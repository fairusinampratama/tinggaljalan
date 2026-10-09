import {chromium} from '@playwright/test';
import {readFile,mkdir,writeFile} from 'node:fs/promises';
import {execFileSync} from 'node:child_process';
const [configFile,revision,out]=process.argv.slice(2);
const config=JSON.parse(await readFile(configFile,'utf8'));
await mkdir(out,{recursive:true});
const browser=await chromium.launch();
const context=await browser.newContext({httpCredentials:{username:'reviewer',password:config.review_password,origin:'https://preview.tinggaljalan.com'}});
try{
 for(const slug of ['Bromo-wildfire','trip-ijen-creater','bukit-lawang-jungle-trekking-orangutan-guide']){
  const response=await context.request.get('https://preview.tinggaljalan.com/news/'+slug);
  if(!response.ok())throw Error('Preview article request failed: '+response.status());
  const file=out+'/'+slug+'.html';await writeFile(file,await response.text());
  let failed=false;try{execFileSync('php',['scripts/deployment/assert-article-html.php',file],{stdio:'pipe'});}catch(e){failed=true;if(!e.stderr.toString().includes('Article section count mismatch'))throw e;}
  if(!failed)throw Error('Expected current deployment validator to reproduce section mismatch');
  execFileSync('php',['scripts/testing/assert-article-html.proposed.php',file],{stdio:'pipe'});
  console.log('VALIDATOR_REPRO '+slug+': current validator rejects wrapper; proposed validator preserves full H2/paragraph/order checks and passes');
  execFileSync('php',['scripts/testing/validator-negative-review.php',file,out],{stdio:'inherit'});
 }
}finally{await browser.close();}
