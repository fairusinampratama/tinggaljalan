<?php

// Incident diagnostics only. Never boot Laravel or print environment values/SQL payloads.
ini_set('zend.exception_ignore_args', '1');
$root = '/home/u304629909/domains/tinggaljalan.com';
$current = $root.'/deployments/current';
$shared = $root.'/deployments/shared';
$phase = 'layout';
try {
    echo 'UTC: '.gmdate('c')."\n";
    echo 'PHP CLI: '.PHP_VERSION."\n";
    echo 'Extensions: '.implode(',', array_filter(['pdo_mysql', 'mbstring', 'intl', 'dom', 'curl', 'zip', 'gd'], fn ($e) => extension_loaded($e)))."\n";
    echo 'Current release: '.(realpath($current) ?: 'missing')."\n";
    echo 'Revision: '.(is_file($current.'/REVISION') ? trim(file_get_contents($current.'/REVISION')) : 'missing')."\n";
    foreach (['.env' => $shared.'/.env', 'config-cache' => $current.'/bootstrap/cache/config.php', 'vendor' => $current.'/vendor/autoload.php', 'maintenance' => $shared.'/storage/framework/down'] as $label => $file) {
        echo $label.': '.json_encode(['exists' => is_file($file), 'readable' => is_readable($file), 'mode' => file_exists($file) ? substr(sprintf('%o', fileperms($file)), -4) : null, 'mtime' => is_file($file) ? gmdate('c', filemtime($file)) : null])."\n";
    }
    foreach (['logs', 'framework/cache/data', 'framework/sessions', 'framework/views', 'app/public'] as $directory) {
        $path = $shared.'/storage/'.$directory;
        echo 'Storage '.$directory.': '.json_encode(['exists' => is_dir($path), 'readable' => is_readable($path), 'writable' => is_writable($path), 'mode' => file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : null])."\n";
    }
    $logs = array_merge(glob($shared.'/storage/logs/*.log') ?: [], [$root.'/public_html/error_log', $root.'/error_log']);
    usort($logs, fn ($a, $b) => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
    foreach (array_slice(array_filter($logs, 'is_file'), 0, 5) as $file) {
        echo 'Log '.basename($file).': size='.filesize($file).'; mtime='.gmdate('c', filemtime($file))."\n";
        $f = fopen($file, 'r');
        fseek($f, max(0, filesize($file) - 262144));
        $tail = stream_get_contents($f);
        fclose($f);
        $entries = [];
        foreach (explode("\n", $tail) as $line) {
            if (! preg_match('/\[([^\]]+)\].*?(?:ERROR|CRITICAL|EMERGENCY)/', $line, $date)) {
                continue;
            }
            $categories = [];
            foreach (['framework/sessions', 'framework/views', 'framework/cache', 'storage/logs', 'bootstrap/cache'] as $directory) {
                if (str_contains($line, $directory)) {
                    $categories[] = 'write-directory:'.$directory;
                }
            }
            foreach (['Permission denied', 'No space left on device', 'Disk quota exceeded', 'Failed opening required', 'Composer detected issues', 'requires a PHP version', 'Vite manifest not found', 'could not find driver', 'No application encryption key', 'Access denied for user', 'Connection refused', 'Too many connections', 'server has gone away', 'Unknown database', 'No such file or directory', 'Allowed memory size', 'Call to undefined function', 'SQLSTATE', 'Base table or view not found', 'syntax error', 'not found', 'read-only file system'] as $reason) {
                if (stripos($line, $reason) !== false) {
                    $categories[] = $reason;
                }
            }
            preg_match('/SQLSTATE\[([A-Z0-9]+)\](?:\s*\[(\d+)\])?/', $line, $sql);
            preg_match('/"exception":"\[object\] \(([^() ]+)/', $line, $class);
            preg_match_all('~ at (/[^ :()]+\.php):(\d+)~', $line, $locations, PREG_SET_ORDER);
            $entries[] = ['date' => $date[1], 'categories' => $categories, 'sqlstate' => $sql[1] ?? null, 'driver_code' => $sql[2] ?? null, 'exception' => $class[1] ?? null, 'locations' => array_map(fn ($m) => basename($m[1]).':'.$m[2], array_slice($locations, 0, 2))];
        }
        foreach (array_slice($entries, -12) as $entry) {
            echo 'Sanitized error: '.json_encode($entry)."\n";
        }
    }
    $phase = 'vendor';
    require $current.'/vendor/autoload.php';
    $phase = 'configuration';
    $env = Dotenv\Dotenv::parse(file_get_contents($shared.'/.env'));
    echo 'Production configuration flags: '.json_encode(['production' => ($env['APP_ENV'] ?? '') === 'production', 'debug_disabled' => ($env['APP_DEBUG'] ?? '') === 'false', 'app_key_present' => ! empty($env['APP_KEY']), 'db_url_present' => ! empty($env['DB_URL'])])."\n";
    $config = is_file($current.'/bootstrap/cache/config.php') ? require $current.'/bootstrap/cache/config.php' : null;
    $db = $config['database']['connections']['mysql'] ?? [];
    echo 'Cached configuration: '.json_encode(['present' => is_array($config), 'production' => ($config['app']['env'] ?? '') === 'production', 'debug_disabled' => ($config['app']['debug'] ?? true) === false, 'db_name_matches_env' => ($db['database'] ?? null) === ($env['DB_DATABASE'] ?? null), 'db_host_matches_env' => ($db['host'] ?? null) === ($env['DB_HOST'] ?? null), 'db_password_matches_env' => ($db['password'] ?? null) === ($env['DB_PASSWORD'] ?? null), 'cache_driver' => $config['cache']['default'] ?? null, 'session_driver' => $config['session']['driver'] ?? null])."\n";
    $phase = 'database';
    if (! in_array($env['DB_HOST'] ?? '', ['localhost', '127.0.0.1'], true) || ($env['DB_DATABASE'] ?? '') !== 'u304629909_tinggaljalan' || ! empty($env['DB_URL'])) {
        throw new RuntimeException;
    }
    $pdo = new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
    $pdo->exec('SET TRANSACTION READ ONLY');
    $pdo->beginTransaction();
    echo 'Production DB connected, read-only SELECT 1: '.$pdo->query('SELECT 1')->fetchColumn()."\n";
    foreach (['site_settings', 'tour_packages', 'hero_slides', 'reviews', 'news_articles', 'bookings'] as $table) {
        echo 'DB row count '.$table.': '.$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn()."\n";
    }
    $pdo->rollBack();
    echo "Read-only diagnostics completed.\n";
} catch (Throwable $error) {
    echo 'Diagnostic phase failed: '.$phase.'; exception='.get_class($error).'; sqlstate='.$error->getCode().'; driver_code='.($error instanceof PDOException ? ($error->errorInfo[1] ?? 'none') : 'none')."\n";
    foreach (['Permission denied', 'No space left on device', 'Disk quota exceeded', 'Access denied for user', 'Connection refused', 'Too many connections', 'server has gone away', 'Unknown database', 'No such file or directory', 'could not find driver'] as $reason) {
        if (stripos($error->getMessage(), $reason) !== false) {
            echo 'Diagnostic error category: '.$reason."\n";
        }
    }
    exit(1);
}
