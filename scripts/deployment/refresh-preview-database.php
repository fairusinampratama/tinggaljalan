<?php

use App\Support\ResponsiveImageGenerator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

ini_set('zend.exception_ignore_args', '1');
ini_set('display_errors', '0');
ini_set('log_errors', '0');
umask(0077);
$stage = 'layout';
$target = null;
$state = null;
$operation = $argv[1] ?? '';
$root = '/home/u304629909/domains/preview.tinggaljalan.com';
$productionRoot = '/home/u304629909/domains/tinggaljalan.com';
$deploy = $root.'/deployments';
$shared = $deploy.'/shared';
$pending = $deploy.'/.database-refresh-pending.json';
$offline = $root.'/public_html/.htaccess';

try {
    require __DIR__.'/preview-db-refresh-lib.php';
    refreshAssert(in_array($operation, ['refresh', 'finalize', 'rollback'], true));
    refreshAssert(realpath($root) === $root && trim(file_get_contents($root.'/.tinggaljalan-staging')) === 'preview.tinggaljalan.com');
    foreach ([$deploy, $shared, $root.'/public_html', $deploy.'/incoming'] as $directory) {
        refreshAssert(realpath($directory) === $directory && ! is_link($directory));
    }
    foreach ([$pending, $offline, $offline.'.refresh-next', $deploy.'/.lock', $shared.'/.env'] as $file) {
        refreshAssert(! is_link($file));
    }
    $lock = fopen($deploy.'/.lock', 'c');
    refreshAssert($lock !== false && flock($lock, LOCK_EX | LOCK_NB));
    if ($operation !== 'finalize' && is_file($pending)) {
        // Close HTTP before any recovery precondition can fail.
        $closed = "# Pending preview database refresh: fail closed\nRewriteEngine On\nRewriteRule ^ - [R=503,L]\n".file_get_contents($offline);
        refreshAssert(file_put_contents($offline.'.refresh-next', $closed) === strlen($closed));
        chmod($offline.'.refresh-next', 0644);
        refreshAssert(rename($offline.'.refresh-next', $offline));
    }
    $release = realpath($deploy.'/current');
    $revision = trim(file_get_contents($release.'/REVISION'));
    refreshAssert((bool) preg_match('/^[a-f0-9]{40}$/', $revision) && $release === $deploy.'/releases/'.$revision);
    require $release.'/vendor/autoload.php';
    require __DIR__.'/staging-config.php';
    $input = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR);
    stagingValidatePasswords($input);
    // Validate existing preview .env; never import production config or APP_KEY.
    stagingConfigure($shared, $release.'/.env.staging.example', $input, fn () => 'u304629909_tj_preview');
    $env = Dotenv\Dotenv::parse(file_get_contents($shared.'/.env'));
    $effective = require $release.'/bootstrap/cache/config.php';
    refreshAssert($effective['app']['env'] === 'staging' && $effective['app']['url'] === 'https://preview.tinggaljalan.com');
    refreshAssert($effective['database']['default'] === 'mysql' && $effective['database']['connections']['mysql']['database'] === 'u304629909_tj_preview');
    refreshAssert($effective['mail']['default'] === 'log' && $effective['cache']['default'] === 'file' && $effective['session']['driver'] === 'file');
    $app = require $release.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    refreshAssert(Http::preventingStrayRequests());
    foreach (['SESSION_DRIVER' => 'file', 'CACHE_STORE' => 'file', 'SESSION_DOMAIN' => 'preview.tinggaljalan.com', 'SESSION_COOKIE' => 'tinggaljalan_preview_session', 'APP_MAINTENANCE_DRIVER' => 'file', 'FILESYSTEM_DISK' => 'local', 'BROADCAST_CONNECTION' => 'log'] as $key => $value) {
        refreshAssert(($env[$key] ?? null) === $value);
    }
    $target = new PDO('mysql:host=localhost;dbname=u304629909_tj_preview;charset=utf8mb4', 'u304629909_tj_preview', $input['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    refreshAssert($target->query('SELECT DATABASE()')->fetchColumn() === 'u304629909_tj_preview');
    $policy = json_decode(file_get_contents(__DIR__.'/preview-db-schema.json'), true, flags: JSON_THROW_ON_ERROR);
    $schema = refreshSchema($target);
    $restore = function () use ($target, $schema, $pending, $offline, $deploy, $shared): void {
        $record = json_decode(file_get_contents($pending), true, flags: JSON_THROW_ON_ERROR);
        $closed = "# Preview database restore: fail closed\nRewriteEngine On\nRewriteRule ^ - [R=503,L]\n".base64_decode($record['htaccess'], true);
        refreshAssert(file_put_contents($offline.'.refresh-next', $closed) === strlen($closed));
        chmod($offline.'.refresh-next', 0644);
        refreshAssert(rename($offline.'.refresh-next', $offline));
        refreshAssert(preg_match('/^preview-before-[0-9]+-[0-9]+\.jsonl\.gz$/', $record['backup']) === 1);
        refreshRestore($target, $schema, $deploy.'/database-backups/'.$record['backup'], $record['digest']);
        if (isset($record['media_baseline'])) {
            refreshRestoreMedia($shared, $record['media_baseline']);
        }
        refreshAssert(file_put_contents($offline.'.refresh-next', base64_decode($record['htaccess'], true)) !== false);
        chmod($offline.'.refresh-next', 0644);
        refreshAssert(rename($offline.'.refresh-next', $offline));
        if (is_file($shared.'/storage/framework/down')) {
            unlink($shared.'/storage/framework/down');
        }
        unlink($pending);
        echo "Previous preview database and matching media restored; production not modified.\n";
    };
    if ($operation === 'rollback') {
        if (is_file($pending)) {
            $restore();
        } else {
            echo "No pending refresh; no database changes made.\n";
        }
        exit;
    }
    if ($operation === 'finalize') {
        refreshAssert(is_file($pending));
        $record = json_decode(file_get_contents($pending), true, flags: JSON_THROW_ON_ERROR);
        refreshAssert(hash_file('sha256', $productionRoot.'/deployments/shared/.env') === $record['production_env_hash']);
        refreshAssert(trim(file_get_contents($productionRoot.'/deployments/current/REVISION')) === $record['production_revision']);
        // Browser checks add explicitly synthetic bookings; do not re-run the pristine-copy verifier here.
        refreshPruneBackups($deploy.'/database-backups', $record['backup']);
        refreshAssert(unlink($pending));
        refreshAssert(unlink($argv[2]));
        echo "Preview refresh finalized after browser verification; protected preview backup retained on Hostinger.\n";
        exit;
    }
    refreshAssert(! is_file($pending) && ! is_file($shared.'/storage/framework/down'));
    $stage = 'source boundaries';
    $productionEnvPath = $productionRoot.'/deployments/shared/.env';
    $sourceEnv = Dotenv\Dotenv::parse(file_get_contents($productionEnvPath));
    refreshAssert(in_array($sourceEnv['DB_HOST'] ?? '', ['localhost', '127.0.0.1'], true) && empty($sourceEnv['DB_URL']));
    refreshAssert($sourceEnv['DB_DATABASE'] === 'u304629909_tinggaljalan' && $sourceEnv['DB_USERNAME'] !== 'u304629909_tj_preview');
    refreshAssert((bool) preg_match('/^u304629909_[a-zA-Z0-9_]+$/', $sourceEnv['DB_DATABASE']));
    $source = new PDO('mysql:host='.$sourceEnv['DB_HOST'].';dbname='.$sourceEnv['DB_DATABASE'].';charset=utf8mb4', $sourceEnv['DB_USERNAME'], $sourceEnv['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // Only SELECT/SHOW queries follow this enforced read-only transaction.
    $source->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $source->exec('SET TRANSACTION READ ONLY');
    $source->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
    refreshAssert($source->query('SELECT DATABASE()')->fetchColumn() === $sourceEnv['DB_DATABASE']);
    foreach ($source->query('SELECT password FROM users')->fetchAll(PDO::FETCH_COLUMN) as $hash) {
        refreshAssert(! password_verify($input['admin_password'], $hash));
    }
    $stage = 'schema policy';
    refreshValidateSchema(refreshSchema($source), $schema, $policy);
    // A refresh may not run alongside preview queue workers or scheduled commands.
    exec('ps -u '.escapeshellarg((string) posix_geteuid()).' -o args=', $processes, $status);
    refreshAssert($status === 0);
    foreach ($processes as $process) {
        refreshAssert(! (str_contains($process, $root) && preg_match('/queue:work|queue:listen|horizon|schedule:/', $process)));
    }
    $run = $argv[3] ?? '';
    refreshAssert((bool) preg_match('/^[0-9]+-[0-9]+$/', $run));
    $backups = $deploy.'/database-backups';
    refreshAssert(! is_link($backups));
    if (! is_dir($backups)) {
        refreshAssert(mkdir($backups, 0700));
    }
    refreshAssert(realpath($backups) === $backups);
    chmod($backups, 0700);
    $backup = $backups.'/preview-before-'.$run.'.jsonl.gz';
    refreshAssert(! file_exists($backup) && ! is_link($backup));
    $htaccess = file_get_contents($offline);
    refreshAssert(str_contains($htaccess, 'Require valid-user') && str_contains($htaccess, $shared.'/.htpasswd'));
    $stage = 'offline gate';
    $blocked = "# Preview database refresh: fail closed\nRewriteEngine On\nRewriteRule ^ - [R=503,L]\n".$htaccess;
    refreshAssert(file_put_contents($offline.'.refresh-next', $blocked) === strlen($blocked));
    chmod($offline.'.refresh-next', 0644);
    refreshAssert(rename($offline.'.refresh-next', $offline));
    // Also block Laravel CLI-driven web requests; HTTP gate includes /up and assets.
    refreshAssert(file_put_contents($shared.'/storage/framework/down', json_encode(['time' => time(), 'retry' => 60, 'status' => 503], JSON_THROW_ON_ERROR)) !== false);
    $probe = curl_init('https://preview.tinggaljalan.com/up');
    curl_setopt_array($probe, [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => 'reviewer:'.$input['review_password'], CURLOPT_TIMEOUT => 30]);
    curl_exec($probe);
    refreshAssert(curl_getinfo($probe, CURLINFO_RESPONSE_CODE) === 503);
    curl_close($probe);
    $target->exec('SET FOREIGN_KEY_CHECKS=0');
    $target->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $target->beginTransaction();
    $stage = 'preview backup';
    $digest = refreshBackup($target, $schema, $backup);
    $mediaBaseline = refreshManagedMediaState($shared);
    $state = ['media_baseline' => $mediaBaseline, 'backup' => basename($backup), 'digest' => $digest, 'htaccess' => base64_encode($htaccess), 'production_env_hash' => hash_file('sha256', $productionEnvPath), 'production_revision' => trim(file_get_contents($productionRoot.'/deployments/current/REVISION'))];
    refreshAssert(file_put_contents($pending, json_encode($state, JSON_THROW_ON_ERROR), LOCK_EX) !== false);
    $stage = 'copy and sanitize';
    $counts = refreshCopy($source, $target, $schema);
    refreshSanitize($target, $input['admin_password']);
    $stage = 'sanitization verification';
    refreshVerifySanitized($target, $input['admin_password']);
    foreach (['destinations', 'tour_packages', 'itinerary_items', 'package_price_tiers', 'package_add_ons', 'package_availabilities', 'news_articles', 'bookings'] as $table) {
        refreshAssert((int) $target->query('SELECT COUNT(*) FROM '.refreshIdentifier($table))->fetchColumn() === $counts[$table]);
    }
    refreshAssert($counts['destinations'] > 0 && $counts['tour_packages'] > 0);
    refreshAssert(hash_file('sha256', $productionEnvPath) === $state['production_env_hash']);
    refreshAssert(trim(file_get_contents($productionRoot.'/deployments/current/REVISION')) === $state['production_revision']);
    $stage = 'public tourism images';
    $imageCount = refreshCopyPublicImages($target, $productionRoot, $shared);
    $stage = 'canonical media consistency';
    refreshVerifyMedia($target, $shared, $release);
    $stage = 'functional route selection';
    $GLOBALS['refreshDetail'] = 'functional-route-format';
    $route = refreshFunctionalRoute($target);
    $GLOBALS['refreshDetail'] = 'functional-route-write';
    refreshAssert(file_put_contents(__DIR__.'/functional-route.json', json_encode(['functional_route' => $route], JSON_THROW_ON_ERROR)) !== false);
    unset($GLOBALS['refreshDetail']);
    $target->commit();
    $target->exec('SET FOREIGN_KEY_CHECKS=1');
    $source->rollBack();
    $stage = 'responsive tourism images';
    $variantCount = refreshGenerateMedia($target, $shared, $release, new ResponsiveImageGenerator);
    // Existing immutable media bytes must remain intact throughout the refresh.
    foreach ($mediaBaseline as $relative => $digest) {
        refreshAssert(hash_file('sha256', refreshCanonicalFile($shared.'/storage/app/public', $relative)) === $digest);
    }
    $stage = 'preview cache';
    foreach (['sessions', 'cache/data'] as $part) {
        $path = $shared.'/storage/framework/'.$part;
        refreshAssert(realpath($path) === $path);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            refreshAssert(! $file->isLink());
            if ($file->isFile() && $file->getFilename() !== '.gitignore') {
                refreshAssert(unlink($file->getPathname()));
            }
        }
    }
    refreshAssert(file_put_contents($offline.'.refresh-next', $htaccess) === strlen($htaccess));
    chmod($offline.'.refresh-next', 0644);
    refreshAssert(unlink($shared.'/storage/framework/down'));
    refreshAssert(rename($offline.'.refresh-next', $offline));
    echo 'Public tourism images copied into separate preview storage: '.$imageCount."\n";
    echo 'DB media references and responsive WebP variants verified; new variants generated: '.$variantCount."\n";
    echo 'Preview revision: '.$revision."\n";
    echo "Preview database: u304629909_tj_preview; production connection read-only; raw production dump never created.\n";
    foreach (['destinations', 'tour_packages', 'itinerary_items', 'package_price_tiers', 'news_articles', 'bookings'] as $table) {
        echo 'Copied '.$table.' rows: '.$counts[$table]."\n";
    }
    echo "Sanitization verified; production users, tokens, sessions, queue payloads and payment records removed; preview admin reset; all integrations disabled.\n";
    echo "Preview reopened with sanitized committed data; backup retained pending functional verification.\n";
} catch (Throwable $error) {
    if ($target instanceof PDO && $target->inTransaction()) {
        $target->rollBack();
    }
    if ($operation === 'refresh' && isset($restore) && is_file($pending)) {
        try {
            $restore();
        } catch (Throwable) {
            fwrite(STDERR, "Preview restore failed; offline gate must remain closed. Use rollback after resolving the host error.\n");
        }
    }
    if ($operation === 'refresh' && ! is_file($pending) && isset($htaccess)) {
        // A failed gate/backup cannot have copied rows: previous DB remains intact.
        file_put_contents($offline.'.refresh-next', $htaccess);
        chmod($offline.'.refresh-next', 0644);
        rename($offline.'.refresh-next', $offline);
        if (is_file($shared.'/storage/framework/down')) {
            unlink($shared.'/storage/framework/down');
        }
    }
    if (isset($GLOBALS['refreshDetail'])) {
        fwrite(STDERR, 'Safe failure category: '.$GLOBALS['refreshDetail'].PHP_EOL);
    }
    if ($error instanceof PDOException) {
        fwrite(STDERR, 'Database diagnostic code: '.(int) ($error->errorInfo[1] ?? 0).PHP_EOL);
    }
    foreach (['quota', 'Permission denied', 'No space left', 'Invalid JSON'] as $reason) {
        if (str_contains($error->getMessage(), $reason)) {
            fwrite(STDERR, 'Host error category: '.$reason.PHP_EOL);
        }
    }
    fwrite(STDERR, 'Preview refresh failed at '.$stage.'. No credentials or row values logged.'.PHP_EOL);
    exit(1);
} finally {
    if (isset($input)) {
        unset($input);
    }
}
