<?php

// Runs against disposable MySQL/MariaDB databases in CI, never Hostinger.
require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/../../scripts/deployment/preview-db-refresh-lib.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection()->getPdo();
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
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
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
$db->rollBack();
refreshAssert($db->query('SELECT email FROM bookings')->fetchColumn() === 'real@private.example');
$checks++;
unlink($backup);
unlink($comparison);
echo "Preview database safety tests passed: {$checks} boundary, unknown-schema, sanitization, read-only source, backup, corruption and rollback checks.\n";
