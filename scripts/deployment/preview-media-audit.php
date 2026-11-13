<?php

// Diagnostics only: return fixed categories, never source paths or row values.
function previewMediaAuditCategory(mixed $value, string $root): string
{
    try {
        refreshAssert($value === null || is_string($value));
        $media = refreshMediaPath($value);
    } catch (Throwable) {
        return 'path-policy-rejected';
    }
    if ($media === null) {
        return 'empty';
    }
    if ($media[0] !== 'upload') {
        return $media[0];
    }
    if (str_starts_with($media[1], 'admin/preview-refresh/')) {
        return 'preview-namespace-rejected';
    }
    $file = $root.'/'.$media[1];
    if (is_link($file)) {
        return 'symlink-rejected';
    }
    if (! file_exists($file)) {
        return 'missing-file';
    }
    if (realpath($root) !== $root || realpath($file) !== $file || ! is_file($file)) {
        return 'noncanonical-file-rejected';
    }
    if (! is_readable($file)) {
        return 'unreadable-file';
    }
    try {
        return @refreshValidImage($file) ? 'valid-image' : 'invalid-image';
    } catch (Throwable) {
        return 'image-validator-error';
    }
}

function previewMediaAuditValues(mixed $raw, ?string $nested): array
{
    if ($nested === null || $raw === null || $raw === '') {
        return [$raw];
    }
    $value = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    refreshAssert(is_array($value));
    if ($nested === '*') {
        refreshAssert(array_is_list($value));
        return array_values($value);
    }
    if ($nested === '*.images.*.path') {
        refreshAssert(array_is_list($value));
        $paths = [];
        foreach ($value as $section) {
            refreshAssert(is_array($section));
            refreshAssert(! isset($section['images']) || (is_array($section['images']) && array_is_list($section['images'])));
            foreach ($section['images'] ?? [] as $image) {
                refreshAssert(is_array($image));
                if (array_key_exists('path', $image)) {
                    $paths[] = $image['path'];
                }
            }
        }

        return $paths;
    }

    return [$value[$nested] ?? null];
}

function previewMediaAudit(PDO $db, string $source): array
{
    refreshAssert(realpath($source) === $source && is_dir($source));
    $records = [];
    // This manifest is the same explicit public-image allowlist used by refresh.
    foreach (refreshMediaFields() as $table => $fields) {
        foreach ($fields as $column => $nested) {
            $counts = [];
            $query = 'SELECT '.refreshIdentifier($column).' FROM '.refreshIdentifier($table);
            foreach ($db->query($query)->fetchAll(PDO::FETCH_COLUMN) as $raw) {
                try {
                    $values = previewMediaAuditValues($raw, $nested);
                } catch (Throwable) {
                    $counts['unreviewed-structure'] = ($counts['unreviewed-structure'] ?? 0) + 1;

                    continue;
                }
                foreach ($values as $value) {
                    $category = previewMediaAuditCategory($value, $source);
                    $counts[$category] = ($counts[$category] ?? 0) + 1;
                }
            }
            ksort($counts);
            $records[] = ['field' => $table.'.'.$column, 'categories' => $counts];
        }
    }

    return $records;
}

if (defined('PREVIEW_MEDIA_AUDIT_LIBRARY_ONLY')) {
    return;
}

ini_set('display_errors', '0');
ini_set('zend.exception_ignore_args', '1');
$db = null;
try {
    $production = '/home/u304629909/domains/tinggaljalan.com';
    $preview = '/home/u304629909/domains/preview.tinggaljalan.com';
    require $preview.'/deployments/current/vendor/autoload.php';
    require $preview.'/deployments/current/scripts/deployment/preview-db-refresh-lib.php';
    $env = Dotenv\Dotenv::parse(file_get_contents($production.'/deployments/shared/.env'));
    refreshAssert(in_array($env['DB_HOST'] ?? '', ['localhost', '127.0.0.1'], true));
    refreshAssert(($env['DB_DATABASE'] ?? '') === 'u304629909_tinggaljalan' && empty($env['DB_URL']));
    $db = new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    unset($env);
    $db->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $db->exec('SET TRANSACTION READ ONLY');
    $db->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
    $records = previewMediaAudit($db, $production.'/deployments/shared/storage/app/public');
    $db->rollBack();
    foreach ($records as $record) {
        echo json_encode($record, JSON_THROW_ON_ERROR).PHP_EOL;
    }
    echo "Read-only public media audit completed; no hosting files or database rows changed.\n";
} catch (Throwable) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Public media audit failed; private diagnostic details withheld.\n");
    exit(1);
}
