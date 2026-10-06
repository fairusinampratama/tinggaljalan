<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Runs against disposable MySQL/MariaDB databases in CI, never Hostinger.
require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/../../scripts/deployment/preview-db-refresh-lib.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = DB::connection()->getPdo();
refreshAssert($db->query('SELECT DATABASE()')->fetchColumn() === 'preview_refresh_test');
$policy = json_decode(file_get_contents(__DIR__.'/../../scripts/deployment/preview-db-schema.json'), true, flags: JSON_THROW_ON_ERROR);
$schema = refreshSchema($db);
refreshValidateSchema($schema, $schema, $policy);
$checks = 0;
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
$now = gmdate('Y-m-d H:i:s');
refreshInsert($db, 'bookings', ['booking_code' => 'REAL-PRIVATE-CODE', 'name' => 'Real Customer', 'email' => 'real@private.example', 'whatsapp' => '628123456789', 'pickup' => 'Private hotel room', 'notes' => 'Passport and personal information', 'travel_date' => '2026-12-01', 'selected_add_ons' => '{"private":"payload"}', 'voucher_code' => 'PRIVATE-CODE', 'payment_gateway' => 'midtrans', 'admin_notification_error' => 'Private gateway payload', 'total' => 123456, 'created_at' => $now, 'updated_at' => $now]);
refreshInsert($db, 'sessions', ['id' => 'real-session', 'payload' => 'private-payload', 'last_activity' => time()]);
refreshInsert($db, 'password_reset_tokens', ['email' => 'real@private.example', 'token' => 'real-token']);
refreshInsert($db, 'settings', ['group' => 'private', 'key' => 'token', 'value' => '"secret"']);
refreshUpdate($db, 'payment_settings', ['is_enabled' => 1, 'public_key' => 'real-public-key', 'secret_key' => 'real-secret-key', 'manual_bank_accounts' => '{"account":"real-account"}']);
refreshUpdate($db, 'email_gateway_settings', ['is_enabled' => 1, 'password' => 'real-smtp-password']);
refreshUpdate($db, 'whatsapp_gateway_settings', ['is_enabled' => 1, 'api_token' => 'real-whatsapp-token']);
refreshUpdate($db, 'notification_settings', ['is_enabled' => 1, 'admin_email' => 'real-admin@private.example', 'admin_whatsapp_number' => '628111111']);
$backup = sys_get_temp_dir().'/preview-refresh-test-'.bin2hex(random_bytes(8)).'.jsonl.gz';
$digest = refreshBackup($db, $schema, $backup);
refreshAssert((fileperms($backup) & 0777) === 0600);
$adminPassword = 'synthetic-preview-admin-password';
$db->exec('SET FOREIGN_KEY_CHECKS=0');
$db->beginTransaction();
refreshSanitize($db, $adminPassword);
refreshVerifySanitized($db, $adminPassword);
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
$target = new PDO('mysql:host=127.0.0.1;dbname=preview_refresh_target;charset=utf8mb4', 'root', 'test-password', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
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
unlink($targetBackup);
$media = sys_get_temp_dir().'/preview-media-'.bin2hex(random_bytes(8));
$sourceImages = $media.'/production/deployments/shared/storage/app/public/admin/hero';
$previewShared = $media.'/preview/shared';
mkdir($sourceImages, 0755, true);
mkdir($previewShared.'/storage/app/public/admin', 0755, true);
$image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII=');
file_put_contents($sourceImages.'/public.png', $image);
refreshUpdate($target, 'hero_slides', ['desktop_image' => 'admin/hero/public.png', 'mobile_image' => '/storage/admin/hero/public.png']);
refreshAssert(refreshCopyPublicImages($target, $media.'/production', $previewShared) === 1);
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
unlink($sourceImages.'/public.png');
$db->rollBack();
refreshAssert($db->query('SELECT email FROM bookings')->fetchColumn() === 'real@private.example');
$checks++;
unlink($backup);
unlink($comparison);
echo "Preview database safety tests passed: {$checks} boundary, unknown-schema, sanitization, read-only source, backup, corruption and rollback checks.\n";
