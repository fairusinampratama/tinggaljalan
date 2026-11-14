<?php

use App\Support\ResponsiveImageGenerator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Runs against disposable MySQL/MariaDB databases in CI, never Hostinger.
require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/../../scripts/deployment/preview-db-refresh-lib.php';
define('PREVIEW_MEDIA_AUDIT_LIBRARY_ONLY', true);
require __DIR__.'/../../scripts/deployment/preview-media-audit.php';
define('PREVIEW_MEDIA_GENERATION_LIBRARY_ONLY', true);
require __DIR__.'/../../scripts/deployment/preview-media-generation-check.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
// Laravel's console bootstrap can otherwise render an uncaught exception and
// leave a directly invoked PHP script with a successful process exit status.
set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, 'Preview database safety test failed: '.get_class($error).PHP_EOL);
    exit(1);
});
if (($argv[1] ?? '') === '--verify-failure-exit') {
    throw new RuntimeException('Intentional disposable safety-harness failure.');
}
$db = DB::connection()->getPdo();
refreshAssert($db->query('SELECT DATABASE()')->fetchColumn() === 'preview_refresh_test');
$policy = json_decode(file_get_contents(__DIR__.'/../../scripts/deployment/preview-db-schema.json'), true, flags: JSON_THROW_ON_ERROR);
$schema = refreshSchema($db);
refreshValidateSchema($schema, $schema, $policy);
$checks = 0;
$identityFixture = tempnam(sys_get_temp_dir(), 'preview-identity-');
file_put_contents($identityFixture, 'synthetic-configuration');
$identity = refreshFileIdentity($identityFixture);
refreshAssert(array_keys($identity) === ['dev', 'ino', 'mode', 'uid', 'gid', 'size', 'mtime', 'ctime']);
file_put_contents($identityFixture, 'changed-synthetic-configuration-size');
refreshAssert(refreshFileIdentity($identityFixture) !== $identity);
unlink($identityFixture);
$checks++;
$expectFailure = function (callable $call) use (&$checks): void {
    try {
        $call();
    } catch (RuntimeException) {
        $checks++;

        return;
    }
    throw new RuntimeException('Unsafe operation accepted.');
};
$expectFailure(fn () => refreshIdentifier('bookings; DROP DATABASE production'));
$unknown = $schema;
$unknown['private_customers'] = ['id', 'passport'];
$expectFailure(fn () => refreshValidateSchema($unknown, $unknown, $policy));
$column = $schema;
$column['bookings'][] = 'passport_number';
$expectFailure(fn () => refreshValidateSchema($column, $column, $policy));
$expectFailure(fn () => refreshValidateSchema($schema, $column, $policy));
Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
$db->beginTransaction();
refreshUpdate($db, 'tour_packages', ['is_active' => 0]);
$firstTour = (int) $db->query('SELECT id FROM tour_packages ORDER BY id LIMIT 1')->fetchColumn();
$db->prepare('UPDATE tour_packages SET slug = ?, is_active = 1, base_price_idr = 100000 WHERE id = ?')->execute(['BROMO', $firstTour]);
refreshAssert(refreshFunctionalRoute($db) === 'BROMO');
$checks++;
$db->prepare('UPDATE tour_packages SET slug = ? WHERE id = ?')->execute(['Bromo-International', $firstTour]);
refreshAssert(refreshFunctionalRoute($db) === 'Bromo-International');
$checks++;
$db->prepare('UPDATE tour_packages SET slug = ? WHERE id = ?')->execute(['unsafe/route?secret', $firstTour]);
$expectFailure(fn () => refreshFunctionalRoute($db));
$db->rollBack();
$now = gmdate('Y-m-d H:i:s');
refreshInsert($db, 'jobs', ['queue' => 'default', 'payload' => 'production-notification-payload', 'attempts' => 0, 'available_at' => time(), 'created_at' => time()]);
refreshInsert($db, 'cache', ['key' => 'production-private-cache', 'value' => 'private-runtime', 'expiration' => time() + 600]);
refreshInsert($db, 'cache_locks', ['key' => 'production-lock', 'owner' => 'production-owner', 'expiration' => time() + 600]);
refreshInsert($db, 'bookings', ['booking_code' => 'REAL-PRIVATE-CODE', 'name' => 'Real Customer', 'email' => 'real@private.example', 'whatsapp' => '628123456789', 'pickup' => 'Private hotel room', 'notes' => 'Passport and personal information', 'travel_date' => '2026-12-01', 'selected_add_ons' => '{"private":"payload"}', 'voucher_code' => 'PRIVATE-CODE', 'payment_gateway' => 'midtrans', 'admin_notification_error' => 'Private gateway payload', 'total' => 123456, 'created_at' => $now, 'updated_at' => $now]);
refreshInsert($db, 'sessions', ['id' => 'real-session', 'payload' => 'private-payload', 'last_activity' => time()]);
refreshInsert($db, 'password_reset_tokens', ['email' => 'real@private.example', 'token' => 'real-token']);
refreshInsert($db, 'settings', ['group' => 'private', 'key' => 'token', 'value' => '"secret"']);
refreshUpdate($db, 'payment_settings', ['is_enabled' => 1, 'public_key' => 'real-public-key', 'secret_key' => 'real-secret-key', 'manual_bank_accounts' => '{"account":"real-account"}']);
refreshUpdate($db, 'email_gateway_settings', ['is_enabled' => 1, 'password' => 'real-smtp-password']);
refreshUpdate($db, 'whatsapp_gateway_settings', ['is_enabled' => 1, 'api_token' => 'real-whatsapp-token']);
refreshUpdate($db, 'notification_settings', ['is_enabled' => 1, 'admin_email' => 'real-admin@private.example', 'admin_whatsapp_number' => '628111111']);
refreshUpdate($db, 'reviews', ['name' => 'Published Traveler', 'origin' => '{"us":"Singapore"}', 'source' => '{"us":"Public review platform"}', 'text' => '{"us":"Published tourism review."}', 'rating' => 4.5, 'review_count' => 17, 'sort_order' => 42]);
refreshUpdate($db, 'tour_packages', ['testimonials' => '[{"name":"Public Traveler","meta":{"us":"Public source"},"quote":{"us":"Published package testimonial."}}]', 'review_source' => '{"us":"Published review source"}']);
refreshUpdate($db, 'site_settings', ['contact_email' => 'public@example.org', 'whatsapp_number' => '628111111111', 'business_address' => 'Published business office', 'google_maps_url' => 'https://maps.example.org/public-office', 'service_hours' => '{"us":"Every day"}', 'service_areas' => '["East Java"]', 'trust_badges' => '["Local guides"]']);
refreshInsert($db, 'package_availabilities', ['tour_package_id' => $firstTour, 'date' => '2026-12-20', 'status' => 'blocked', 'reason' => 'Public local closure explanation', 'notes' => 'Private driver contact and operational notes']);
refreshUpdate($db, 'route_filters', ['description' => '{"us":"Private internal taxonomy note"}']);
refreshUpdate($db, 'hero_slides', ['admin_label' => 'Private campaign planning name']);
refreshInsert($db, 'vouchers', ['code' => 'PRIVATE-CUSTOMER-CODE', 'label' => 'Private customer gift', 'is_public' => 0, 'discount_type' => 'percent', 'discount_value' => 10, 'public_title' => '{"us":"Private draft"}', 'public_description' => '{"us":"Private draft description"}']);
$temporarySections = '[{"heading":{"us":"Public heading"},"body":{"us":"Public prose"},"images":[{"path":"livewire-file:synthetic-pending.jpg","alt":{"us":"Public alt"},"caption":{}},{"path":"livewire-files:[\"synthetic-pending.jpg\"]","alt":{"us":"Public alt"},"caption":{"us":"Public credit"}}]}]';
refreshUpdate($db, 'news_articles', ['sections' => $temporarySections]);
$temporarySections = $db->query('SELECT sections FROM news_articles LIMIT 1')->fetchColumn();
$expectedTemporarySections = json_decode($temporarySections);
foreach ($expectedTemporarySections[0]->images as $temporaryImage) {
    $temporaryImage->path = null;
}
refreshAssert(json_decode(refreshArticleSectionsWithoutUploadHandles($temporarySections)) == $expectedTemporarySections);
refreshAssert(refreshArticleSectionsWithoutUploadHandles(refreshArticleSectionsWithoutUploadHandles($temporarySections)) === refreshArticleSectionsWithoutUploadHandles($temporarySections));
$checks++;
$publicSnapshot = refreshPublicContentSnapshot($db);
$backup = sys_get_temp_dir().'/preview-refresh-test-'.bin2hex(random_bytes(8)).'.jsonl.gz';
$digest = refreshBackup($db, $schema, $backup);
refreshAssert((fileperms($backup) & 0777) === 0600);
$adminPassword = 'synthetic-preview-admin-password';
$db->exec('SET FOREIGN_KEY_CHECKS=0');
$db->beginTransaction();
refreshSanitize($db, $adminPassword);
refreshVerifySanitized($db, $adminPassword);
refreshAssert(json_decode($db->query('SELECT sections FROM news_articles LIMIT 1')->fetchColumn()) == $expectedTemporarySections);
refreshAssert(refreshPublicContentSnapshot($db) === $publicSnapshot);
refreshAssert($db->query('SELECT reason FROM package_availabilities ORDER BY id DESC LIMIT 1')->fetchColumn() === 'Public local closure explanation');
refreshAssert($db->query('SELECT notes FROM package_availabilities ORDER BY id DESC LIMIT 1')->fetchColumn() === null);
$checks++;
refreshAssert((int) $db->query('SELECT total FROM bookings')->fetchColumn() === 123456);
refreshAssert((int) $db->query('SELECT COUNT(*) FROM tour_packages')->fetchColumn() > 0);
$checks++;
$db->rollBack();
refreshAssert($db->query('SELECT email FROM bookings')->fetchColumn() === 'real@private.example');
$checks++;
$db->beginTransaction();
refreshSanitize($db, $adminPassword);
refreshVerifySanitized($db, $adminPassword);
$db->commit();
foreach (['whatsapp' => 'real-number', 'notes' => 'private-notes', 'selected_add_ons' => '{"private":"value"}', 'admin_notification_error' => 'private-response'] as $field => $unsafe) {
    refreshUpdate($db, 'bookings', [$field => $unsafe]);
    $expectFailure(fn () => refreshVerifySanitized($db, $adminPassword));
    refreshSanitize($db, $adminPassword);
}
refreshUpdate($db, 'whatsapp_gateway_settings', ['api_token' => 'private-token']);
$expectFailure(fn () => refreshVerifySanitized($db, $adminPassword));
refreshSanitize($db, $adminPassword);
refreshUpdate($db, 'notification_settings', ['email_enabled' => 1]);
$expectFailure(fn () => refreshVerifySanitized($db, $adminPassword));
refreshSanitize($db, $adminPassword);
refreshUpdate($db, 'payment_settings', ['is_enabled' => 1]);
$expectFailure(fn () => refreshVerifySanitized($db, $adminPassword));
refreshRestore($db, $schema, $backup, $digest);
$comparison = $backup.'.comparison';
refreshAssert(refreshBackup($db, $schema, $comparison) === $digest);
$checks++;
$expectFailure(fn () => refreshRestore($db, $schema, $backup, str_repeat('0', 64)));
// Copy a real schema from a read-only snapshot into a separate disposable DB.
$db->exec('CREATE DATABASE preview_refresh_target');
$connectTarget = fn (): PDO => new PDO('mysql:host=127.0.0.1;dbname=preview_refresh_target;charset=utf8mb4', 'root', 'test-password', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$target = $connectTarget();
$target->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($schema as $table => $columns) {
    $create = $db->query('SHOW CREATE TABLE '.refreshIdentifier($table))->fetch(PDO::FETCH_NUM)[1];
    $target->exec($create);
}
$db->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
$db->exec('SET TRANSACTION READ ONLY');
$db->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
$expectFailure(fn () => $db->exec("UPDATE bookings SET name = 'must-not-write'"));
$target->beginTransaction();
$counts = refreshCopy($db, $target, $schema);
refreshSanitize($target, $adminPassword);
refreshVerifySanitized($target, $adminPassword);
refreshVerifyPublicContent($db, $target);
refreshAssert($db->query('SELECT sections FROM news_articles LIMIT 1')->fetchColumn() === $temporarySections);
refreshAssert(json_decode($target->query('SELECT sections FROM news_articles LIMIT 1')->fetchColumn()) == $expectedTemporarySections);
$checks++;
$checks++;
// Public fidelity verification must detect accidental CMS redaction.
$reviewId = (int) $target->query('SELECT id FROM reviews ORDER BY id LIMIT 1')->fetchColumn();
$target->prepare('UPDATE reviews SET name = ? WHERE id = ?')->execute(['Preview Reviewer', $reviewId]);
$expectFailure(fn () => refreshVerifyPublicContent($db, $target));
$target->prepare('UPDATE reviews SET name = ? WHERE id = ?')->execute(['Published Traveler', $reviewId]);
refreshVerifyPublicContent($db, $target);
$expectedTestimonials = $target->query('SELECT testimonials FROM tour_packages LIMIT 1')->fetchColumn();
refreshUpdate($target, 'tour_packages', ['testimonials' => '[{"name":"Public Traveler","quote":{"us":"Public quote"},"booking_id":7,"raw_payment":{"secret":"private"}}]']);
$expectFailure(fn () => refreshValidatePublicContentShapes($target));
refreshUpdate($target, 'tour_packages', ['testimonials' => $expectedTestimonials]);
refreshUpdate($target, 'package_availabilities', ['notes' => 'private internal note']);
$expectFailure(fn () => refreshVerifySanitized($target, $adminPassword));
refreshSanitize($target, $adminPassword);
$privateVoucherId = (int) $target->query('SELECT id FROM vouchers WHERE is_public = 0 ORDER BY id LIMIT 1')->fetchColumn();
$target->prepare('UPDATE vouchers SET code = ? WHERE id = ?')->execute(['PRIVATE-LEAK', $privateVoucherId]);
$expectFailure(fn () => refreshVerifySanitized($target, $adminPassword));
// Reset public voucher codes too before testing source fidelity again.
$target->exec('DELETE FROM vouchers');
foreach ($db->query('SELECT * FROM vouchers')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    refreshInsert($target, 'vouchers', $row);
}
refreshSanitize($target, $adminPassword);
refreshVerifyPublicContent($db, $target);
refreshAssert($counts['bookings'] === 1 && $counts['tour_packages'] > 0);
$target->commit();
// An exception after a partial replacement must recover the pre-copy data.
$targetBackup = $backup.'.target';
$targetDigest = refreshBackup($target, $schema, $targetBackup);
$target->beginTransaction();
$target->exec('DELETE FROM bookings');
$target->rollBack();
refreshAssert((int) $target->query('SELECT COUNT(*) FROM bookings')->fetchColumn() === 1);
$checks++;
$target->exec('DELETE FROM bookings');
refreshRestore($target, $schema, $targetBackup, $targetDigest);
refreshAssert((int) $target->query('SELECT COUNT(*) FROM bookings')->fetchColumn() === 1);
$checks++;
$media = sys_get_temp_dir().'/preview-media-'.bin2hex(random_bytes(8));
$sourceImages = $media.'/production/deployments/shared/storage/app/public/admin/hero';
$previewShared = $media.'/preview/shared';
mkdir($sourceImages, 0755, true);
mkdir($previewShared.'/storage/app/public/admin', 0755, true);
$fixture = imagecreatetruecolor(8, 6);
imagepng($fixture, $sourceImages.'/public.png');
imagedestroy($fixture);
$image = file_get_contents($sourceImages.'/public.png');
refreshUpdate($target, 'hero_slides', ['desktop_image' => 'admin/hero/public.png', 'mobile_image' => '/storage/admin/hero/public.png']);
$previousMask = umask(0077);
refreshAssert(refreshCopyPublicImages($target, $media.'/production', $previewShared) === 1);
umask($previousMask);
refreshAssert((fileperms($previewShared.'/storage/app/public/admin/preview-refresh') & 0777) === 0755);
$checks++;
refreshAssert(file_get_contents($sourceImages.'/public.png') === $image);
refreshAssert(str_starts_with($target->query('SELECT desktop_image FROM hero_slides LIMIT 1')->fetchColumn(), '/storage/admin/preview-refresh/'));
$checks++;
refreshUpdate($target, 'hero_slides', ['desktop_image' => 'admin/../private.png']);
$expectFailure(fn () => refreshCopyPublicImages($target, $media.'/production', $previewShared));
refreshUpdate($target, 'hero_slides', ['desktop_image' => 'admin/hero/public.png', 'mobile_image' => null]);
$copiedImage = $previewShared.'/storage/app/public/admin/preview-refresh/'.hash('sha256', $image).'.png';
unlink($copiedImage);
symlink($sourceImages.'/public.png', $copiedImage);
$expectFailure(fn () => refreshCopyPublicImages($target, $media.'/production', $previewShared));
unlink($copiedImage);
refreshCopyPublicImages($target, $media.'/production', $previewShared);
$targetDigest = refreshBackup($target, $schema, $targetBackup);
$baseline = refreshManagedMediaState($previewShared);
$fixture = imagecreatetruecolor(8, 6);
imagefill($fixture, 0, 0, imagecolorallocate($fixture, 0, 200, 0));
imagepng($fixture, $sourceImages.'/public.png');
imagedestroy($fixture);
$image = file_get_contents($sourceImages.'/public.png');
// Schema-driven scalar, gallery, nested About and logo references.
refreshUpdate($target, 'hero_slides', ['desktop_image' => 'admin/hero/public.png', 'mobile_image' => null]);
refreshUpdate($target, 'about_pages', ['hero' => '{"image":"admin/hero/public.png","title":{"us":"Public story"}}', 'story' => '{"image":null}', 'seo' => '{"image":"https://example.org/public-image.png"}']);
refreshUpdate($target, 'platform_links', ['logo' => 'admin/hero/public.png']);
refreshUpdate($target, 'site_settings', ['logo_url' => 'admin/hero/public.png']);
file_put_contents($sourceImages.'/public.png', $image);
refreshAssert(refreshCopyPublicImages($target, $media.'/production', $previewShared) === 1);
$paths = refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2));
refreshAssert(count($paths) === 1);
$checks++;
$originalStoragePath = $app->storagePath();
$app->useStoragePath($previewShared.'/storage');
// Reproduce a lost shared-host connection without waiting for an idle timeout.
// The committed media manifest allows filesystem work to finish with no PDO.
$connectionId = (int) $target->query('SELECT CONNECTION_ID()')->fetchColumn();
$killer = $connectTarget();
$killer->exec('KILL CONNECTION '.$connectionId);
$expectFailure(fn () => $target->query('SELECT 1'));
refreshAssert(refreshGenerateMedia($paths, $previewShared, new ResponsiveImageGenerator) === 5);
$target = $connectTarget();
refreshAssert(refreshSchema($target) === $schema);
$checks++;
$expectFailure(fn () => refreshGenerateMedia(['admin/payments/private.png'], $previewShared, new ResponsiveImageGenerator));
refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2), true);
// Failure categories reveal the failed rule, never paths, IDs or image bytes.
$variant = $previewShared.'/storage/app/public/'.(new ResponsiveImageGenerator)->generatedPublicDiskPath($paths[0], 480);
$validVariant = file_get_contents($variant);
unlink($variant);
clearstatcache(true, $variant);
$expectFailure(fn () => refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2), true));
refreshAssert($GLOBALS['refreshDetail'] === 'responsive-variant-file');
file_put_contents($variant, $image);
clearstatcache(true, $variant);
$expectFailure(fn () => refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2), true));
refreshAssert($GLOBALS['refreshDetail'] === 'responsive-variant-format');
$wrongSize = imagecreatetruecolor(1, 1);
imagewebp($wrongSize, $variant);
imagedestroy($wrongSize);
clearstatcache(true, $variant);
$expectFailure(fn () => refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2), true));
refreshAssert($GLOBALS['refreshDetail'] === 'responsive-variant-width');
file_put_contents($variant, $validVariant);
clearstatcache(true, $variant);
refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2), true);
refreshAssert(! isset($GLOBALS['refreshDetail']));
// Diagnose every source reference without replacing a DB or emitting values.
$sourcePublic = $media.'/production/deployments/shared/storage/app/public';
$diagnosticPublic = $previewShared.'/storage/app/public';
$generator = new ResponsiveImageGenerator;
refreshAssert(previewCheckGeneratedImage('/storage/admin/hero/public.png', $sourcePublic, $diagnosticPublic, $generator) === 'responsive-image-verified');
$checks++;
$wrongSize = imagecreatetruecolor(2, 2);
imagegif($wrongSize, $sourceImages.'/unsupported.png');
imagedestroy($wrongSize);
refreshAssert(previewCheckGeneratedImage('/storage/admin/hero/unsupported.png', $sourcePublic, $diagnosticPublic, $generator) === 'unsupported-raster-decoder');
unlink($sourceImages.'/unsupported.png');
$checks++;
file_put_contents($sourceImages.'/header-only.png', substr($image, 0, 33));
refreshAssert(previewCheckGeneratedImage('/storage/admin/hero/header-only.png', $sourcePublic, $diagnosticPublic, $generator) === 'raster-decode-failed');
unlink($sourceImages.'/header-only.png');
$checks++;
foreach (['jpg' => 'imagejpeg', 'webp' => 'imagewebp'] as $extension => $encoder) {
    $raster = imagecreatetruecolor(2401, 801);
    $path = $sourceImages.'/format.'.$extension;
    $encoder($raster, $path);
    imagedestroy($raster);
    $originalBytes = file_get_contents($path);
    refreshAssert(previewCheckGeneratedImage('/storage/admin/hero/format.'.$extension, $sourcePublic, $diagnosticPublic, $generator) === 'responsive-image-verified');
    refreshAssert(file_get_contents($path) === $originalBytes);
    unlink($path);
    $checks++;
}
$report = previewCheckMediaReferences(['hero_slides.desktop_image' => ['invalid' => 0, 'values' => [
    '/storage/private/customer-canary.png', '/storage/admin/hero/missing-canary.png', '/storage/admin/hero/public.png', 'livewire-file:temporary-canary',
]]], $sourcePublic, $diagnosticPublic, $generator);
refreshAssert($report['failed'] && $report['records'][0]['categories'] === [
    'missing-file' => 1, 'path-policy-rejected' => 1, 'responsive-image-verified' => 1, 'temporary-upload-handle' => 1,
]);
foreach (['canary', '/storage/', $sourcePublic, $diagnosticPublic] as $canary) {
    refreshAssert(! str_contains(json_encode($report), $canary));
}
refreshAssert(file_get_contents($sourceImages.'/public.png') === $image);
$checks++;
$expectFailure(fn () => previewCheckMediaReferences(['private-canary' => ['invalid' => 0, 'values' => []]], $sourcePublic, $diagnosticPublic, $generator));
$app->useStoragePath($originalStoragePath);
refreshAssert(previewCheckGeneratedImage('/storage/admin/hero/public.png', $sourcePublic, $diagnosticPublic, $generator) === 'diagnostic-storage-boundary');
$checks++;
$checks++;
$expectFailure(fn () => refreshMediaRoots($media.'/production', $media.'/production/deployments/shared'));
$expectFailure(fn () => refreshMediaPath('/storage/admin/payments/receipt.png'));
$expectFailure(fn () => refreshMediaPath('/storage/uploads/customer.png'));
$expectFailure(fn () => refreshMediaPath('/storage/admin/hero/%2e%2e/private.png'));
$expectFailure(fn () => refreshMediaPath('https://user@tinggaljalan.com/storage/admin/hero/public.png'));
$expectFailure(fn () => refreshMediaPath('/storage/framework/sessions/customer.png'));
$expectFailure(fn () => refreshMediaPath('/storage/private/passport.png'));
$expectFailure(fn () => refreshMediaPath('/storage/admin/hero/../../private.png'));
$expectFailure(fn () => refreshMediaPath('/storage/admin/hero/missing.exe'));
$expectFailure(fn () => refreshMediaPath('livewire-file:synthetic-pending.jpg'));
$expectFailure(fn () => refreshMediaPath('livewire-files:["synthetic-pending.jpg"]'));
refreshAssert(refreshMediaPath(null) === null && refreshMediaPath('') === null);
refreshAssert(refreshMediaPath('https://example.org/logo.png')[0] === 'external');
$checks++;
$copiedImage = $previewShared.'/storage/app/public/'.$paths[0];
rename($copiedImage, $copiedImage.'.held');
$expectFailure(fn () => refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2)));
rename($copiedImage.'.held', $copiedImage);
refreshUpdate($target, 'hero_slides', ['desktop_image' => 'admin/hero/missing.png']);
$expectFailure(fn () => refreshCopyPublicImages($target, $media.'/production', $previewShared));
refreshAssert(refreshPreviewLink('https://tinggaljalan.com/routes/BROMO') === '/routes/BROMO');
refreshAssert(refreshPreviewLink('https://example.org/public-tour') === 'https://example.org/public-tour');
foreach (['https://wa.me/628111111', 'whatsapp', 'mailto:real@example.org', 'https://tinggaljalan.com/checkout/payment/private-token', 'javascript:alert(1)'] as $unsafe) {
    refreshAssert(refreshPreviewLink($unsafe) === null);
}
$checks++;
refreshUpdate($target, 'hero_slides', ['desktop_image' => 'admin/hero/public.png', 'primary_cta_url' => 'https://tinggaljalan.com/routes/BROMO', 'secondary_cta_url' => 'https://wa.me/628111111']);
refreshSanitizeLinks($target);
refreshAssert($target->query('SELECT primary_cta_url FROM hero_slides LIMIT 1')->fetchColumn() === '/routes/BROMO');
refreshAssert($target->query('SELECT secondary_cta_url FROM hero_slides LIMIT 1')->fetchColumn() === null);
$checks++;
file_put_contents($sourceImages.'/active.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
refreshAssert(! refreshValidImage($sourceImages.'/active.svg'));
file_put_contents($sourceImages.'/active.svg', '<svg xmlns="http://www.w3.org/2000/svg"><set attributeName="href" to="javascript:alert(1)"/></svg>');
refreshAssert(! refreshValidImage($sourceImages.'/active.svg'));
file_put_contents($sourceImages.'/active.svg', '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 L1 1"/></svg>');
refreshAssert(refreshValidImage($sourceImages.'/active.svg'));
unlink($sourceImages.'/active.svg');
$checks++;
// Recovery must use a fresh connection even when the failed one is gone.
$target->exec('DELETE FROM bookings');
$connectionId = (int) $target->query('SELECT CONNECTION_ID()')->fetchColumn();
$killer->exec('KILL CONNECTION '.$connectionId);
$expectFailure(fn () => $target->query('SELECT 1'));
$target = $connectTarget();
refreshAssert(refreshSchema($target) === $schema);
// A committed new DB plus media is restored as one logical snapshot.
refreshRestore($target, $schema, $targetBackup, $targetDigest);
refreshRestoreMedia($previewShared, $baseline);
refreshAssert(refreshManagedMediaState($previewShared) === $baseline);
refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2));
refreshAssert((int) $target->query('SELECT COUNT(*) FROM bookings')->fetchColumn() === 1);
refreshAssert(file_get_contents($sourceImages.'/public.png') === $image);
$checks++;
$retention = $media.'/preview/database-backups';
// Current admin/public rendering also supports public photographs inside sections.
$inlineDirectory = $media.'/production/deployments/shared/storage/app/public/admin/news/sections';
mkdir($inlineDirectory, 0755, true);
$inlineFixture = imagecreatetruecolor(7, 5);
imagefill($inlineFixture, 0, 0, imagecolorallocate($inlineFixture, 0, 0, 200));
imagepng($inlineFixture, $inlineDirectory.'/public.png');
imagedestroy($inlineFixture);
$inlineBytes = file_get_contents($inlineDirectory.'/public.png');
$target->beginTransaction();
refreshVisitMedia($target, fn ($path) => $path === null ? null : '');
$section = [
    'heading' => ['us' => 'Public editorial heading'],
    'body' => ['us' => 'Public article prose.'],
    'images' => [
        ['path' => 'admin/news/sections/public.png', 'alt' => ['us' => 'Public photo description'], 'caption' => ['us' => 'Published photographer credit']],
        ['path' => 'livewire-file:synthetic-inline.jpg', 'alt' => ['us' => 'Public planned photo'], 'caption' => ['us' => 'Public planned credit']],
    ],
];
refreshUpdate($target, 'news_articles', ['sections' => json_encode([$section], JSON_THROW_ON_ERROR)]);
$expectFailure(fn () => refreshVerifySanitized($target, $adminPassword));
refreshSanitize($target, $adminPassword);
refreshVerifySanitized($target, $adminPassword);
refreshAssert(refreshCopyPublicImages($target, $media.'/production', $previewShared) === 1);
$copiedSection = json_decode($target->query('SELECT sections FROM news_articles LIMIT 1')->fetchColumn(), true, flags: JSON_THROW_ON_ERROR)[0];
refreshAssert($copiedSection['heading'] === $section['heading'] && $copiedSection['body'] === $section['body']);
refreshAssert($copiedSection['images'][0]['alt'] === $section['images'][0]['alt'] && $copiedSection['images'][0]['caption'] === $section['images'][0]['caption']);
refreshAssert($copiedSection['images'][1]['path'] === null);
refreshAssert($copiedSection['images'][1]['alt'] === $section['images'][1]['alt'] && $copiedSection['images'][1]['caption'] === $section['images'][1]['caption']);
refreshAssert(str_starts_with($copiedSection['images'][0]['path'], '/storage/admin/preview-refresh/'));
refreshAssert(file_get_contents($inlineDirectory.'/public.png') === $inlineBytes);
refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2));
$app->useStoragePath($previewShared.'/storage');
refreshAssert(refreshGenerateMedia(refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2)), $previewShared, new ResponsiveImageGenerator) === 5);
refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2), true);
$app->useStoragePath($originalStoragePath);
$target->rollBack();
refreshRestoreMedia($previewShared, $baseline);
refreshVerifyMedia($target, $previewShared, dirname(__DIR__, 2));
refreshAssert(refreshManagedMediaState($previewShared) === $baseline);
$checks++;
// Other invalid/private paths remain untouched by this narrow sanitizer and
// must still fail the original media boundary. Never turn it into a fallback.
foreach (['/storage/private/customer.png', 'admin/news/sections/../../private.png'] as $invalidPath) {
    $invalidSections = json_encode([['images' => [['path' => $invalidPath]]]], JSON_THROW_ON_ERROR);
    refreshAssert(refreshArticleSectionsWithoutUploadHandles($invalidSections) === $invalidSections);
    $expectFailure(fn () => refreshMediaPath($invalidPath));
}
mkdir($retention, 0700);
foreach ([1, 2, 3, 4, 5] as $number) {
    file_put_contents($retention.'/preview-before-'.$number.'-1.jsonl.gz', 'synthetic-preview-backup');
    touch($retention.'/preview-before-'.$number.'-1.jsonl.gz', 1000 + $number);
}
refreshPruneBackups($retention, 'preview-before-5-1.jsonl.gz');
refreshAssert(count(glob($retention.'/*.gz')) === 3 && is_file($retention.'/preview-before-5-1.jsonl.gz'));
$checks++;
$expectFailure(fn () => refreshPruneBackups($media.'/production', 'preview-before-5-1.jsonl.gz'));
foreach (glob($retention.'/*.gz') as $file) {
    unlink($file);
}
unlink($targetBackup);
unlink($sourceImages.'/public.png');
$db->rollBack();
refreshAssert($db->query('SELECT email FROM bookings')->fetchColumn() === 'real@private.example');
$checks++;
unlink($backup);
unlink($comparison);
echo "Preview database safety tests passed: {$checks} boundary, unknown-schema, sanitization, read-only source, backup, corruption and rollback checks.\n";
