<?php

use App\Support\ResponsiveImageGenerator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\NullLogger;

// Fixed categories only. Source rows, paths, image bytes and hashes stay private.
function previewCheckGeneratedImage(mixed $value, string $source, string $public, object $generator): string
{
    if (is_string($value) && (str_starts_with($value, 'livewire-file:') || str_starts_with($value, 'livewire-files:'))) {
        return 'temporary-upload-handle';
    }
    $category = previewMediaAuditCategory($value, $source);
    if ($category !== 'valid-image') {
        return $category;
    }
    try {
        $media = refreshMediaPath($value);
        $file = refreshCanonicalFile($source, $media[1]);
        if (str_ends_with(strtolower($file), '.svg')) {
            return 'valid-passive-svg';
        }
        $info = getimagesize($file);
        $nativeFormat = match ($info[2]) {
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_BMP => 'bmp',
            IMAGETYPE_AVIF => 'avif',
            IMAGETYPE_WBMP => 'wbmp',
            IMAGETYPE_ICO => 'ico',
            default => 'other',
        };
        $decoder = match ($info[2]) {
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_WEBP => 'imagecreatefromwebp',
            default => 'imagecreatefromstring',
        };
        if (! function_exists($decoder)) {
            return 'unavailable-raster-decoder';
        }
        $decoded = @$decoder($decoder === 'imagecreatefromstring' ? file_get_contents($file) : $file);
        if ($decoded === false) {
            return $decoder === 'imagecreatefromstring' ? 'raster-decode-failed-'.$nativeFormat : 'raster-decode-failed';
        }
        imagedestroy($decoded);
        $digest = hash_file('sha256', $file);
        $relative = 'admin/preview-refresh/'.$digest.'.'.strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $destination = $public.'/'.$relative;
        refreshEnsureMediaDirectory($public, 'admin/preview-refresh');
        refreshEnsureMediaDirectory($public, 'generated/storage/admin/preview-refresh');
        $GLOBALS['refreshDetail'] = 'diagnostic-copy-failed';
        refreshAssert(! is_link($destination));
        if (! file_exists($destination)) {
            refreshAssert(copy($file, $destination) && chmod($destination, 0600));
        }
        refreshAssert(hash_file('sha256', $destination) === $digest);
        $GLOBALS['refreshDetail'] = 'diagnostic-storage-boundary';
        refreshAssert(realpath(storage_path('app/public')) === $public);
        $generator->generateForPublicDiskPath($relative, true);
        refreshVerifyResponsiveImage($public, $relative);
        $GLOBALS['refreshDetail'] = 'source-changed-during-check';
        refreshAssert(hash_file('sha256', $file) === $digest);
        unset($GLOBALS['refreshDetail']);

        return $decoder === 'imagecreatefromstring' ? 'responsive-image-verified-'.$nativeFormat : 'responsive-image-verified';
    } catch (Throwable) {
        $category = $GLOBALS['refreshDetail'] ?? 'generation-check-error';
        unset($GLOBALS['refreshDetail']);

        return in_array($category, ['canonical-media-file', 'responsive-variant-file', 'responsive-variant-format', 'responsive-variant-width', 'diagnostic-copy-failed', 'diagnostic-storage-boundary', 'source-changed-during-check'], true) ? $category : 'generation-check-error';
    }
}

function previewCollectMediaReferences(PDO $db): array
{
    $references = [];
    foreach (refreshMediaFields() as $table => $fields) {
        foreach ($fields as $column => $nested) {
            $values = [];
            $invalid = 0;
            foreach ($db->query('SELECT '.refreshIdentifier($column).' FROM '.refreshIdentifier($table))->fetchAll(PDO::FETCH_COLUMN) as $raw) {
                try {
                    array_push($values, ...previewMediaAuditValues($raw, $nested));
                } catch (Throwable) {
                    $invalid++;
                }
            }
            $references[$table.'.'.$column] = ['values' => $values, 'invalid' => $invalid];
        }
    }

    return $references;
}

function previewCheckMediaReferences(array $references, string $source, string $public, object $generator): array
{
    $accepted = ['empty', 'static', 'external', 'temporary-upload-handle', 'valid-passive-svg', 'responsive-image-verified', 'responsive-image-verified-gif', 'responsive-image-verified-bmp', 'responsive-image-verified-avif', 'responsive-image-verified-wbmp', 'responsive-image-verified-ico', 'responsive-image-verified-other'];
    $allowedFields = [];
    foreach (refreshMediaFields() as $table => $fields) {
        foreach (array_keys($fields) as $column) {
            $allowedFields[] = $table.'.'.$column;
        }
    }
    $records = [];
    $failed = false;
    foreach ($references as $field => $reference) {
        refreshAssert(in_array($field, $allowedFields, true));
        $counts = $reference['invalid'] ? ['unreviewed-structure' => $reference['invalid']] : [];
        $failed = $failed || $reference['invalid'] > 0;
        foreach ($reference['values'] as $value) {
            $category = previewCheckGeneratedImage($value, $source, $public, $generator);
            $counts[$category] = ($counts[$category] ?? 0) + 1;
            $failed = $failed || ! in_array($category, $accepted, true);
        }
        ksort($counts);
        $records[] = ['field' => $field, 'categories' => $counts];
    }

    return ['records' => $records, 'failed' => $failed];
}

if (defined('PREVIEW_MEDIA_GENERATION_LIBRARY_ONLY')) {
    return;
}

ini_set('display_errors', '0');
ini_set('log_errors', '0');
ini_set('zend.exception_ignore_args', '1');
umask(0077);
$exit = 1;
$scratch = null;
$ownedScratch = false;
$stage = 'runtime-boundaries';
try {
    require __DIR__.'/preview-db-refresh-lib.php';
    define('PREVIEW_MEDIA_AUDIT_LIBRARY_ONLY', true);
    require __DIR__.'/preview-media-audit.php';
    $preview = '/home/u304629909/domains/preview.tinggaljalan.com';
    $production = '/home/u304629909/domains/tinggaljalan.com';
    $incoming = $preview.'/deployments/incoming';
    refreshAssert(realpath($incoming) === $incoming && realpath(__DIR__) === __DIR__);
    refreshAssert(preg_match('~^'.preg_quote($incoming, '~').'/media-check-[0-9]+-[0-9]+$~', __DIR__) === 1);
    $accountPath = '/home/u304629909/.tinggaljalan-maintenance.lock';
    refreshAssert(! is_link($accountPath));
    $accountLock = fopen($accountPath, 'c');
    refreshAssert($accountLock !== false && flock($accountLock, LOCK_EX | LOCK_NB));
    foreach (['.database-refresh-pending.json', '.preview-release-pending.json'] as $journal) {
        refreshAssert(! file_exists($preview.'/deployments/'.$journal) && ! is_link($preview.'/deployments/'.$journal));
    }
    $stage = 'read-only-capacity-reserve';
    exec('python3 '.escapeshellarg(__DIR__.'/hosting-maintenance.py').' capacity --reserve 2>/dev/null', $capacityOutput, $capacityStatus);
    refreshAssert($capacityStatus === 0);
    echo "Read-only hosting capacity reserve check passed under account lock.\n";
    $stage = 'runtime-boundaries';
    $release = realpath($preview.'/deployments/current');
    refreshAssert(is_string($release) && preg_match('~^'.preg_quote($preview, '~').'/deployments/releases/[a-f0-9]{40}$~', $release) === 1);
    require $release.'/vendor/autoload.php';
    $app = require $release.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    Log::swap(new NullLogger);
    refreshAssert(app()->environment('staging') && config('app.url') === 'https://preview.tinggaljalan.com' && Http::preventingStrayRequests());
    $shared = $preview.'/deployments/shared';
    refreshAssert(realpath(storage_path('app/public')) === $shared.'/storage/app/public');
    $effective = require $release.'/bootstrap/cache/config.php';
    $targetConfig = $effective['database']['connections']['mysql'];
    refreshAssert($targetConfig['database'] === 'u304629909_tj_preview' && $targetConfig['username'] === 'u304629909_tj_preview');
    $connectPreview = function () use ($targetConfig): PDO {
        $db = new PDO('mysql:host=localhost;dbname=u304629909_tj_preview;charset=utf8mb4', $targetConfig['username'], $targetConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        refreshAssert($db->query('SELECT DATABASE()')->fetchColumn() === 'u304629909_tj_preview');
        $db->exec('SET TRANSACTION READ ONLY');
        $db->beginTransaction();

        return $db;
    };
    $stage = 'preview-schema-before';
    $target = $connectPreview();
    $schema = refreshSchema($target);
    $policy = json_decode(file_get_contents(__DIR__.'/preview-db-schema.json'), true, flags: JSON_THROW_ON_ERROR);
    refreshValidateSchema($schema, $schema, $policy);
    $target->rollBack();
    $target = null;
    $stage = 'source-public-reference-snapshot';
    $env = Dotenv\Dotenv::parse(file_get_contents($production.'/deployments/shared/.env'));
    refreshAssert(in_array($env['DB_HOST'] ?? '', ['localhost', '127.0.0.1'], true) && empty($env['DB_URL']));
    refreshAssert($env['DB_DATABASE'] === 'u304629909_tinggaljalan' && $env['DB_USERNAME'] !== 'u304629909_tj_preview');
    $source = new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$env['DB_DATABASE'].';charset=utf8mb4', $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    unset($env);
    $source->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $source->exec('SET TRANSACTION READ ONLY');
    $source->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
    $references = previewCollectMediaReferences($source);
    $source->rollBack();
    $source = null;
    // No source/target PDO connection remains open during CPU/filesystem work.
    $scratch = __DIR__.'/scratch';
    refreshAssert(! file_exists($scratch) && ! is_link($scratch) && mkdir($scratch, 0700));
    $ownedScratch = true;
    $stage = 'private-scratch-generation';
    $public = $scratch.'/storage/app/public';
    refreshAssert(mkdir($public, 0700, true) && realpath($public) === $public);
    $app->useStoragePath($scratch.'/storage');
    $generator = new ResponsiveImageGenerator;
    refreshAssert($generator->isSupported());
    $result = previewCheckMediaReferences($references, $production.'/deployments/shared/storage/app/public', $public, $generator);
    $exit = $result['failed'] ? 2 : 0;
    foreach ($result['records'] as $record) {
        echo json_encode($record, JSON_THROW_ON_ERROR).PHP_EOL;
    }
    $stage = 'preview-schema-after';
    $target = $connectPreview();
    refreshAssert(refreshSchema($target) === $schema);
    $target->rollBack();
    $target = null;
    echo "Preview schema unchanged across generation; no database writes; no production files changed.\n";
} catch (Throwable) {
    $exit = 1;
    fwrite(STDERR, 'Media generation diagnostic failed at '.$stage.'; private details withheld.'.PHP_EOL);
} finally {
    if (isset($app, $shared)) {
        $app->useStoragePath($shared.'/storage');
    }
    try {
        if ($ownedScratch && is_dir($scratch) && ! is_link($scratch) && realpath($scratch) === $scratch) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scratch, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir() && ! $file->isLink()) {
                    refreshAssert(rmdir($file->getPathname()));
                } else {
                    refreshAssert(unlink($file->getPathname()));
                }
            }
            refreshAssert(rmdir($scratch));
        }
    } catch (Throwable) {
        $exit = 1;
        fwrite(STDERR, "Private diagnostic scratch cleanup failed; hosting refresh remains unverified.\n");
    }
}
exit($exit);
