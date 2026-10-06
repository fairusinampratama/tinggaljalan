<?php

// Pure PDO operations: no Eloquent events, observers, mail or provider calls.
function refreshAssert(bool $condition): void
{
    if (! $condition) {
        throw new RuntimeException('Preview refresh safety check failed.');
    }
}

function refreshIdentifier(string $name): string
{
    refreshAssert((bool) preg_match('/^[a-z][a-z0-9_]*$/', $name));

    return '`'.$name.'`';
}

function refreshSchema(PDO $db): array
{
    $schema = [];
    foreach ($db->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$table, $type]) {
        refreshAssert($type === 'BASE TABLE');
        $quoted = refreshIdentifier($table);
        $columns = $db->query('SHOW FULL COLUMNS FROM '.$quoted)->fetchAll(PDO::FETCH_ASSOC);
        $schema[$table] = array_column($columns, 'Field');
        refreshAssert($db->query('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.$db->quote($table))->fetchColumn() === 'InnoDB');
        refreshAssert((int) $db->query('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = '.$db->quote($table))->fetchColumn() === 0);
    }
    refreshAssert((int) $db->query('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_SCHEMA IS NOT NULL AND REFERENCED_TABLE_SCHEMA <> DATABASE()')->fetchColumn() === 0);
    ksort($schema);

    return $schema;
}

function refreshValidateSchema(array $source, array $target, array $policy): void
{
    ksort($source);
    ksort($target);
    ksort($policy);
    refreshAssert($source === $target && $source === $policy);
}

function refreshInsert(PDO $db, string $table, array $row): void
{
    refreshAssert($row !== []);
    $columns = implode(',', array_map('refreshIdentifier', array_keys($row)));
    $db->prepare('INSERT INTO '.refreshIdentifier($table).' ('.$columns.') VALUES ('.implode(',', array_fill(0, count($row), '?')).')')->execute(array_values($row));
}

function refreshUpdate(PDO $db, string $table, array $values): void
{
    $set = implode(',', array_map(fn ($key) => refreshIdentifier($key).' = ?', array_keys($values)));
    $db->prepare('UPDATE '.refreshIdentifier($table).' SET '.$set)->execute(array_values($values));
}

function refreshSanitize(PDO $db, string $adminPassword): void
{
    // Delete auth and opaque payload containers instead of trusting nested values.
    foreach (['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'booking_payments', 'settings'] as $table) {
        $db->exec('DELETE FROM '.refreshIdentifier($table));
    }
    $now = gmdate('Y-m-d H:i:s');
    refreshInsert($db, 'users', ['name' => 'Preview Admin', 'email' => 'preview-admin@tinggaljalan.test', 'password' => password_hash($adminPassword, PASSWORD_BCRYPT), 'is_admin' => 1, 'email_verified_at' => null, 'remember_token' => null, 'created_at' => $now, 'updated_at' => $now]);
    $db->exec("UPDATE bookings SET booking_code = CONCAT('PREVIEW-', id), name = CONCAT('Preview Customer ', id), email = CONCAT('customer-', id, '@example.invalid')");
    refreshUpdate($db, 'bookings', ['whatsapp' => null, 'whatsapp_country' => null, 'pickup' => 'Preview pickup', 'notes' => null, 'selected_add_ons' => null, 'voucher_code' => null, 'payment_gateway' => null, 'travel_date' => null, 'admin_notification_attempted_at' => null, 'admin_whatsapp_sent_at' => null, 'admin_whatsapp_failed_at' => null, 'admin_email_sent_at' => null, 'admin_email_failed_at' => null, 'admin_notification_error' => null]);
    $db->exec("UPDATE reviews SET name = CONCAT('Preview Reviewer ', id)");
    refreshUpdate($db, 'reviews', ['origin' => null, 'source' => null, 'text' => '{"id":"Contoh ulasan preview.","us":"Preview example review."}']);
    refreshUpdate($db, 'tour_packages', ['testimonials' => null, 'review_source' => null]);
    refreshUpdate($db, 'package_availabilities', ['reason' => null, 'notes' => null]);
    // Public contact links are neutralized too, preventing accidental real contact.
    refreshUpdate($db, 'site_settings', ['whatsapp_number' => null, 'contact_email' => 'preview@example.invalid', 'business_address' => null, 'google_maps_url' => null, 'logo_url' => '/images/logo-tj.png']);
    refreshUpdate($db, 'email_gateway_settings', ['provider' => 'log', 'is_enabled' => 0, 'host' => null, 'port' => null, 'username' => null, 'password' => null, 'scheme' => null, 'from_address' => 'preview@example.invalid', 'from_name' => 'TinggalJalan Preview', 'last_tested_at' => null, 'last_test_status' => null, 'last_test_message' => null]);
    refreshUpdate($db, 'whatsapp_gateway_settings', ['provider' => 'manual', 'is_enabled' => 0, 'api_base_url' => null, 'api_token' => null, 'session_id' => null, 'manual_fallback_enabled' => 0, 'last_tested_at' => null, 'last_test_status' => null, 'last_test_message' => null]);
    refreshUpdate($db, 'notification_settings', ['is_enabled' => 0, 'whatsapp_enabled' => 0, 'email_enabled' => 0, 'admin_whatsapp_number' => null, 'admin_email' => null]);
    refreshUpdate($db, 'payment_settings', ['is_enabled' => 0, 'mode' => 'sandbox', 'public_key' => null, 'secret_key' => null, 'manual_bank_accounts' => null, 'booking_note' => 'Preview only. Payments disabled.', 'usd_note' => 'Preview only. Payments disabled.']);
    // Private voucher codes/labels are not published promotions.
    $db->exec("UPDATE vouchers SET code = CONCAT('PREVIEW-', id), label = CONCAT('Preview voucher ', id), public_title = NULL, public_description = NULL WHERE is_public = 0");
}

function refreshVerifySanitized(PDO $db, string $adminPassword): void
{
    foreach (['password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'booking_payments', 'settings'] as $table) {
        refreshAssert((int) $db->query('SELECT COUNT(*) FROM '.refreshIdentifier($table))->fetchColumn() === 0);
    }
    $users = $db->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC);
    refreshAssert(count($users) === 1 && $users[0]['email'] === 'preview-admin@tinggaljalan.test' && (int) $users[0]['is_admin'] === 1 && $users[0]['remember_token'] === null && password_verify($adminPassword, $users[0]['password']));
    refreshAssert((int) $db->query("SELECT COUNT(*) FROM bookings WHERE NOT (booking_code <=> CONCAT('PREVIEW-', id)) OR NOT (name <=> CONCAT('Preview Customer ', id)) OR NOT (email <=> CONCAT('customer-', id, '@example.invalid')) OR whatsapp IS NOT NULL OR whatsapp_country IS NOT NULL OR pickup <> 'Preview pickup' OR notes IS NOT NULL OR selected_add_ons IS NOT NULL OR voucher_code IS NOT NULL OR payment_gateway IS NOT NULL OR travel_date IS NOT NULL OR admin_notification_error IS NOT NULL")->fetchColumn() === 0);
    foreach (['email_gateway_settings', 'whatsapp_gateway_settings', 'notification_settings', 'payment_settings'] as $table) {
        refreshAssert((int) $db->query('SELECT COUNT(*) FROM '.refreshIdentifier($table))->fetchColumn() > 0);
        refreshAssert((int) $db->query('SELECT COUNT(*) FROM '.refreshIdentifier($table).' WHERE is_enabled <> 0')->fetchColumn() === 0);
    }
    foreach (['payment_settings' => ['public_key', 'secret_key', 'manual_bank_accounts'], 'email_gateway_settings' => ['host', 'username', 'password', 'last_test_message'], 'whatsapp_gateway_settings' => ['api_base_url', 'api_token', 'session_id', 'last_test_message'], 'notification_settings' => ['admin_email', 'admin_whatsapp_number']] as $table => $columns) {
        foreach ($columns as $column) {
            refreshAssert((int) $db->query('SELECT COUNT(*) FROM '.refreshIdentifier($table).' WHERE '.refreshIdentifier($column).' IS NOT NULL')->fetchColumn() === 0);
        }
    }
    refreshAssert((int) $db->query('SELECT COUNT(*) FROM notification_settings WHERE email_enabled <> 0 OR whatsapp_enabled <> 0')->fetchColumn() === 0);
    refreshAssert((int) $db->query("SELECT COUNT(*) FROM payment_settings WHERE mode <> 'sandbox'")->fetchColumn() === 0);
    refreshAssert((int) $db->query('SELECT COUNT(*) FROM whatsapp_gateway_settings WHERE manual_fallback_enabled <> 0')->fetchColumn() === 0);
    refreshAssert((int) $db->query('SELECT COUNT(*) FROM tour_packages WHERE testimonials IS NOT NULL')->fetchColumn() === 0);
    refreshAssert((int) $db->query('SELECT COUNT(*) FROM package_availabilities WHERE reason IS NOT NULL OR notes IS NOT NULL')->fetchColumn() === 0);
    refreshAssert((int) $db->query("SELECT COUNT(*) FROM reviews WHERE name <> CONCAT('Preview Reviewer ', id) OR origin IS NOT NULL OR source IS NOT NULL OR JSON_UNQUOTE(JSON_EXTRACT(text, '$.us')) <> 'Preview example review.' OR JSON_UNQUOTE(JSON_EXTRACT(text, '$.id')) <> 'Contoh ulasan preview.' OR JSON_LENGTH(text) <> 2")->fetchColumn() === 0);
}

function refreshCopy(PDO $source, PDO $target, array $schema): array
{
    $counts = [];
    foreach ($schema as $table => $columns) {
        $target->exec('DELETE FROM '.refreshIdentifier($table));
    }
    foreach ($schema as $table => $columns) {
        $statement = $source->query('SELECT * FROM '.refreshIdentifier($table));
        $counts[$table] = 0;
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            refreshInsert($target, $table, $row);
            $counts[$table]++;
        }
        $statement->closeCursor();
    }

    return $counts;
}

function refreshBackup(PDO $db, array $schema, string $path): string
{
    $file = gzopen($path, 'wb9');
    refreshAssert($file !== false);
    chmod($path, 0600);
    try {
        foreach ($schema as $table => $columns) {
            $statement = $db->query('SELECT * FROM '.refreshIdentifier($table));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $line = json_encode([$table, $row], JSON_THROW_ON_ERROR)."\n";
                refreshAssert(gzwrite($file, $line) === strlen($line));
            }
            $statement->closeCursor();
        }
    } finally {
        gzclose($file);
    }

    return hash_file('sha256', $path);
}

function refreshRestore(PDO $db, array $schema, string $path, string $digest): void
{
    refreshAssert(hash_equals($digest, hash_file('sha256', $path)));
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    $db->beginTransaction();
    try {
        foreach ($schema as $table => $columns) {
            $db->exec('DELETE FROM '.refreshIdentifier($table));
        }
        $file = gzopen($path, 'rb');
        refreshAssert($file !== false);
        try {
            while (($line = gzgets($file)) !== false) {
                [$table, $row] = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                refreshAssert(isset($schema[$table]) && array_keys($row) === $schema[$table]);
                refreshInsert($db, $table, $row);
            }
        } finally {
            gzclose($file);
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    } finally {
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}

function refreshCopyPublicImages(PDO $db, string $productionRoot, string $previewShared): int
{
    $sourceRoot = $productionRoot.'/deployments/shared/storage/app/public';
    $targetRoot = $previewShared.'/storage/app/public/admin/preview-refresh';
    $GLOBALS['refreshDetail'] = 'media-source-root';
    refreshAssert(realpath($sourceRoot) === $sourceRoot);
    $GLOBALS['refreshDetail'] = 'media-destination-directory';
    refreshAssert(! is_link($targetRoot));
    if (! is_dir($targetRoot)) {
        refreshAssert(mkdir($targetRoot, 0755));
    }
    refreshAssert(realpath($targetRoot) === $targetRoot);
    refreshAssert(chmod($targetRoot, 0755));
    $copied = [];
    $replace = function ($value) use (&$replace, &$copied, $sourceRoot, $targetRoot) {
        if (is_array($value)) {
            return array_map($replace, $value);
        }
        if (! is_string($value)) {
            return $value;
        }
        $path = preg_replace('~^https://(?:www\.)?tinggaljalan\.com/~', '/', $value);
        $path = preg_replace('~^(?:/)?(?:storage/|public/)?~', '', $path);
        if (! preg_match('~^(?:admin/|uploads/)[a-zA-Z0-9_./-]+\.(?:jpg|jpeg|png|webp)$~i', $path)) {
            return $value;
        }
        $GLOBALS['refreshDetail'] = 'media-source-path';
        refreshAssert(! in_array('..', explode('/', $path), true));
        $GLOBALS['refreshDetail'] = 'media-source-image';
        $source = realpath($sourceRoot.'/'.$path);
        refreshAssert(is_string($source) && str_starts_with($source, $sourceRoot.'/') && is_file($source) && getimagesize($source) !== false);
        $name = hash_file('sha256', $source).'.'.strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $destination = $targetRoot.'/'.$name;
        $GLOBALS['refreshDetail'] = 'media-destination-file';
        refreshAssert(! is_link($destination));
        if (! is_file($destination)) {
            $GLOBALS['refreshDetail'] = 'media-copy-write';
            refreshAssert(copy($source, $destination));
            chmod($destination, 0644);
        }
        $GLOBALS['refreshDetail'] = 'media-digest';
        refreshAssert(hash_file('sha256', $destination) === hash_file('sha256', $source));
        $copied[$name] = true;

        return '/storage/admin/preview-refresh/'.$name;
    };
    foreach (['destinations' => ['cover_image'], 'tour_packages' => ['cover_image', 'gallery'], 'news_articles' => ['cover_image'], 'hero_slides' => ['desktop_image', 'mobile_image'], 'team_members' => ['portrait'], 'company_milestones' => ['image']] as $table => $columns) {
        foreach ($db->query('SELECT id,'.implode(',', $columns).' FROM '.refreshIdentifier($table))->fetchAll(PDO::FETCH_ASSOC) as $row) {
            foreach ($columns as $column) {
                $original = $row[$column];
                $json = $column === 'gallery';
                $value = $json && $original !== null ? json_decode($original, true, flags: JSON_THROW_ON_ERROR) : $original;
                $updated = $replace($value);
                if ($updated !== $value) {
                    $encoded = $json ? json_encode($updated, JSON_THROW_ON_ERROR) : $updated;
                    $GLOBALS['refreshDetail'] = 'media-row-update';
                    $db->prepare('UPDATE '.refreshIdentifier($table).' SET '.refreshIdentifier($column).' = ? WHERE id = ?')->execute([$encoded, $row['id']]);
                }
            }
        }
    }

    unset($GLOBALS['refreshDetail']);

    return count($copied);
}
