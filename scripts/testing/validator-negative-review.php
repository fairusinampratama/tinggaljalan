<?php
libxml_use_internal_errors(true);
$source=file_get_contents($argv[1]);
$document=new DOMDocument();$document->loadHTML('<?xml encoding="UTF-8">'.$source);
$xpath=new DOMXPath($document);
$wrapper=$xpath->query('//div[contains(@class,"server-document")]')->item(0);
while($wrapper->firstChild)$wrapper->parentNode->insertBefore($wrapper->firstChild,$wrapper);
$wrapper->parentNode->removeChild($wrapper);
$file=$argv[2].'/legacy.html';file_put_contents($file,$document->saveHTML());
exec('php scripts/deployment/assert-article-html.php '.escapeshellarg($file).' 2>&1',$lines,$status);
if($status!==0)throw new RuntimeException('Legacy validator should accept unwrapped sections');
exec('php scripts/testing/assert-article-html.proposed.php '.escapeshellarg($file).' 2>&1',$lines,$status);
if($status!==0)throw new RuntimeException('Proposed validator should accept legacy sections');
foreach(['missing_h2','changed_heading','reordered_sections','missing_paragraph','changed_paragraph','missing_break'] as $case){
 $doc=new DOMDocument();$doc->loadHTML('<?xml encoding="UTF-8">'.$source);$xp=new DOMXPath($doc);
 $sections=$xp->query('//main//section[@id]');$section=$sections->item(0);
 $heading=$xp->query('./h2',$section)->item(0);$paragraph=$xp->query('./p',$section)->item(0);
 if($case==='missing_h2')$section->removeChild($heading);
 if($case==='changed_heading')$heading->textContent='Incorrect heading';
 if($case==='reordered_sections')$section->parentNode->insertBefore($sections->item(1),$section);
 if($case==='missing_paragraph')$section->removeChild($paragraph);
 if($case==='changed_paragraph')$paragraph->textContent='Incomplete article content';
 if($case==='missing_break'){
  $br=$xp->query('//main//section[@id]//br')->item(0);if(!$br){echo "NEGATIVE_REVIEW missing_break: no line break in this article; skipped\n";continue;}$br->parentNode->removeChild($br);
 }
 $file=$argv[2].'/'.$case.'.html';file_put_contents($file,$doc->saveHTML());$lines=[];
 exec('php scripts/testing/assert-article-html.proposed.php '.escapeshellarg($file).' 2>&1',$lines,$status);
 if($status===0)throw new RuntimeException('Validator failed to reject '.$case);
 echo 'NEGATIVE_REVIEW '.$case.": rejected as required\n";
}
echo "LEGACY_REVIEW direct sections pass both validators\n";
