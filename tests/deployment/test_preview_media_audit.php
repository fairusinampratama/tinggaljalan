<?php

// Disposable files and a query-only SQLite DB; never hosting or live credentials.
define('PREVIEW_MEDIA_AUDIT_LIBRARY_ONLY', true);
require __DIR__.'/../../scripts/deployment/preview-db-refresh-lib.php';
require __DIR__.'/../../scripts/deployment/preview-media-audit.php';
define('PREVIEW_MEDIA_GENERATION_LIBRARY_ONLY', true);
require __DIR__.'/../../scripts/deployment/preview-media-generation-check.php';
set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, 'Public media audit safety test failed at line '.$error->getLine().'.'.PHP_EOL);
    exit(1);
});
$root = sys_get_temp_dir().'/media-audit-'.bin2hex(random_bytes(8));
mkdir($root, 0700);
mkdir($root.'/admin');
mkdir($root.'/admin/site');
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j9ZkAAAAASUVORK5CYII=');
file_put_contents($root.'/admin/site/good.png', $png);
file_put_contents($root.'/admin/site/invalid.png', 'private-media-canary');
file_put_contents($root.'/admin/site/passive.svg', '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1v1z"/></svg>');
file_put_contents($root.'/admin/site/active.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>private-canary</script></svg>');
symlink($root.'/admin/site/good.png', $root.'/admin/site/link.png');
symlink($root.'/admin/site', $root.'/admin/packages');
$cases = [
    [null, 'empty'],
    ['', 'empty'],
    ['/images/logo.svg', 'static'],
    ['https://example.invalid/public.png', 'external'],
    ['/storage/admin/site/good.png', 'valid-image'],
    ['/storage/admin/site/missing.png', 'missing-file'],
    ['/storage/admin/site/invalid.png', 'invalid-image'],
    ['/storage/admin/site/passive.svg', 'valid-image'],
    ['/storage/admin/site/active.svg', 'invalid-image'],
    ['/storage/admin/site/link.png', 'symlink-rejected'],
    ['/storage/admin/packages/covers/good.png', 'missing-file'],
    ['/storage/admin/preview-refresh/good.png', 'preview-namespace-rejected'],
    ['/storage/private/customer-passport.png', 'path-policy-rejected'],
    ['/storage/admin/site/../private.png', 'path-policy-rejected'],
    ['private-customer-canary with spaces', 'path-policy-rejected'],
];
foreach ($cases as [$value, $expected]) {
    refreshAssert(previewMediaAuditCategory($value, $root) === $expected);
}
// A symlinked parent must never be accepted even if the image itself is valid.
unlink($root.'/admin/packages');
mkdir($root.'/admin/packages');
symlink($root.'/admin/site', $root.'/admin/packages/covers');
refreshAssert(previewMediaAuditCategory('/storage/admin/packages/covers/good.png', $root) === 'noncanonical-file-rejected');
$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach (refreshMediaFields() as $table => $fields) {
    $definitions = array_map(fn ($field) => refreshIdentifier($field).' TEXT', array_keys($fields));
    $db->exec('CREATE TABLE '.refreshIdentifier($table).' ('.implode(',', $definitions).')');
}
$db->prepare('INSERT INTO site_settings (logo_url) VALUES (?)')->execute(['/storage/admin/site/good.png']);
$db->prepare('INSERT INTO tour_packages (cover_image, gallery) VALUES (?, ?)')->execute([
    '/storage/admin/site/missing.png', json_encode(['/storage/admin/site/invalid.png']),
]);
$db->prepare('INSERT INTO news_articles (cover_image, sections) VALUES (?, ?)')->execute([
    'private-customer-canary', json_encode([['body' => 'private-prose-canary', 'images' => [['path' => '/storage/admin/site/good.png', 'caption' => 'private-caption-canary']]]]),
]);
$db->prepare('INSERT INTO about_pages (hero, story, seo) VALUES (?, ?, ?)')->execute([
    json_encode(['image' => '/storage/admin/site/good.png', 'text' => 'private-about-canary']), '{invalid', '{}',
]);
$before = $db->query('SELECT * FROM news_articles')->fetchAll(PDO::FETCH_ASSOC);
$db->exec('PRAGMA query_only = ON');
$rejectedWrite = false;
try {
    $db->exec('DELETE FROM news_articles');
} catch (PDOException) {
    $rejectedWrite = true;
}
refreshAssert($rejectedWrite);
$records = previewMediaAudit($db, $root);
$references = previewCollectMediaReferences($db);
refreshAssert($references['news_articles.sections']['values'] === ['/storage/admin/site/good.png']);
refreshAssert($references['about_pages.story']['invalid'] === 1);
foreach (['private-prose-canary', 'private-caption-canary', 'private-about-canary'] as $canary) {
    refreshAssert(! str_contains(json_encode($references), $canary));
}
refreshAssert($before === $db->query('SELECT * FROM news_articles')->fetchAll(PDO::FETCH_ASSOC));
$fields = array_column($records, 'categories', 'field');
refreshAssert($fields['site_settings.logo_url'] === ['valid-image' => 1]);
refreshAssert($fields['tour_packages.cover_image'] === ['missing-file' => 1]);
refreshAssert($fields['tour_packages.gallery'] === ['invalid-image' => 1]);
refreshAssert($fields['news_articles.sections'] === ['valid-image' => 1]);
refreshAssert($fields['about_pages.story'] === ['unreviewed-structure' => 1]);
$output = json_encode($records, JSON_THROW_ON_ERROR);
foreach (['private-', '/storage/', $root, 'good.png', 'missing.png', 'invalid.png'] as $canary) {
    refreshAssert(! str_contains($output, $canary));
}
// Diagnostics leave source bytes and directory contents unchanged.
refreshAssert(file_get_contents($root.'/admin/site/good.png') === $png);
refreshAssert(file_get_contents($root.'/admin/site/invalid.png') === 'private-media-canary');
unlink($root.'/admin/packages/covers');
rmdir($root.'/admin/packages');
foreach (glob($root.'/admin/site/*') as $file) {
    unlink($file);
}
rmdir($root.'/admin/site');
rmdir($root.'/admin');
rmdir($root);
echo "Public media audit safety tests passed: image categories, nested CMS paths, query-only database, no mutations and no private output.\n";
