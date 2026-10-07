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

function refreshPublicContentFields(): array
{
    return [
        'reviews' => ['id', 'name', 'origin', 'rating', 'review_count', 'source', 'text', 'is_featured', 'is_active', 'sort_order', 'created_at', 'updated_at'],
        'tour_packages' => ['id', 'testimonials', 'review_source', 'rating', 'review_count', 'notes'],
        'package_availabilities' => ['id', 'tour_package_id', 'destination_id', 'date', 'end_date', 'is_open_ended', 'status', 'seats_left', 'reason'],
        'site_settings' => ['id', 'business_address', 'google_maps_url', 'contact_email', 'whatsapp_number', 'service_hours', 'service_areas', 'trust_badges', 'hero_autoplay_enabled', 'hero_autoplay_interval'],
        'vouchers' => ['id', 'code', 'label', 'public_title', 'public_description', 'discount_type', 'discount_value', 'currency', 'allowed_currencies', 'maximum_discount_idr', 'maximum_discount_usd', 'starts_at', 'ends_at', 'usage_limit', 'is_active', 'is_public', 'sort_order'],
    ];
}

function refreshPublicContentSnapshot(PDO $db): array
{
    $snapshot = [];
    foreach (refreshPublicContentFields() as $table => $columns) {
        $where = $table === 'vouchers' ? ' WHERE is_public = 1' : '';
        $rows = $db->query('SELECT '.implode(',', array_map('refreshIdentifier', $columns)).' FROM '.refreshIdentifier($table).$where.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        // Compare public content in memory; never log values or content digests.
        $snapshot[$table] = ['rows' => count($rows), 'digest' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }

    return $snapshot;
}

function refreshVerifyPublicContent(PDO $source, PDO $target): array
{
    $expected = refreshPublicContentSnapshot($source);
    refreshAssert($expected === refreshPublicContentSnapshot($target));

    return array_map(fn ($entry) => $entry['rows'], $expected);
}

function refreshValidatePublicContentShapes(PDO $db): void
{
    $localized = function ($value): bool {
        return $value === null || is_string($value) || (is_array($value)
            && ! array_diff(array_keys($value), ['id', 'us', 'cn'])
            && ! array_filter($value, fn ($text) => $text !== null && ! is_string($text)));
    };
    foreach (['reviews' => ['origin', 'source', 'text'], 'tour_packages' => ['testimonials', 'review_source']] as $table => $columns) {
        foreach ($db->query('SELECT '.implode(',', $columns).' FROM '.refreshIdentifier($table))->fetchAll(PDO::FETCH_ASSOC) as $row) {
            foreach ($row as $column => $raw) {
                if ($raw === null) {
                    continue;
                }
                $value = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
                if ($column !== 'testimonials') {
                    refreshAssert($localized($value));

                    continue;
                }
                refreshAssert(is_array($value) && array_is_list($value));
                foreach ($value as $item) {
                    // Only the reviewed public CMS shape; refuse opaque/raw booking payloads.
                    refreshAssert(is_array($item) && ! array_diff(array_keys($item), ['name', 'meta', 'quote', 'text']));
                    refreshAssert(is_string($item['name'] ?? '') && $localized($item['meta'] ?? null) && $localized($item['quote'] ?? null) && $localized($item['text'] ?? null));
                }
            }
        }
    }
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
    // Public CMS reviews/testimonials/reasons/business display data stay realistic.
    refreshValidatePublicContentShapes($db);
    refreshUpdate($db, 'package_availabilities', ['notes' => null]);
    refreshUpdate($db, 'route_filters', ['description' => null]);
    $db->exec("UPDATE hero_slides SET admin_label = CONCAT('Preview hero ', id)");
    refreshUpdate($db, 'email_gateway_settings', ['provider' => 'log', 'is_enabled' => 0, 'host' => null, 'port' => null, 'username' => null, 'password' => null, 'scheme' => null, 'from_address' => 'preview@example.invalid', 'from_name' => 'TinggalJalan Preview', 'last_tested_at' => null, 'last_test_status' => null, 'last_test_message' => null]);
    refreshUpdate($db, 'whatsapp_gateway_settings', ['provider' => 'manual', 'is_enabled' => 0, 'api_base_url' => null, 'api_token' => null, 'session_id' => null, 'manual_fallback_enabled' => 0, 'last_tested_at' => null, 'last_test_status' => null, 'last_test_message' => null]);
    refreshUpdate($db, 'notification_settings', ['is_enabled' => 0, 'whatsapp_enabled' => 0, 'email_enabled' => 0, 'admin_whatsapp_number' => null, 'admin_email' => null]);
    refreshUpdate($db, 'payment_settings', ['is_enabled' => 0, 'mode' => 'sandbox', 'public_key' => null, 'secret_key' => null, 'manual_bank_accounts' => null, 'booking_note' => 'Preview only. Payments disabled.', 'usd_note' => 'Preview only. Payments disabled.']);
    refreshSanitizeLinks($db);
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
    refreshValidatePublicContentShapes($db);
    refreshAssert((int) $db->query('SELECT COUNT(*) FROM package_availabilities WHERE notes IS NOT NULL')->fetchColumn() === 0);
    refreshAssert((int) $db->query('SELECT COUNT(*) FROM route_filters WHERE description IS NOT NULL')->fetchColumn() === 0);
    refreshAssert((int) $db->query("SELECT COUNT(*) FROM hero_slides WHERE NOT (admin_label <=> CONCAT('Preview hero ', id))")->fetchColumn() === 0);
    refreshAssert((int) $db->query("SELECT COUNT(*) FROM vouchers WHERE is_public = 0 AND (code <> CONCAT('PREVIEW-', id) OR label <> CONCAT('Preview voucher ', id) OR public_title IS NOT NULL OR public_description IS NOT NULL)")->fetchColumn() === 0);
}

function refreshFunctionalRoute(PDO $db): string
{
    $route = $db->query('SELECT slug FROM tour_packages WHERE is_active = 1 AND (base_price_idr > 0 OR EXISTS (SELECT 1 FROM package_price_tiers WHERE tour_package_id = tour_packages.id AND min_pax <= 2 AND (max_pax IS NULL OR max_pax >= 2) AND price_idr > 0)) ORDER BY id LIMIT 1')->fetchColumn();
    refreshAssert(is_string($route) && preg_match('/^[a-z0-9-]+$/i', $route) === 1);

    return $route;
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

require_once __DIR__.'/preview-media-refresh-lib.php';
