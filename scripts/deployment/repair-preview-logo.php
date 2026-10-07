<?php

// Narrow repair: production reads only; reuse an already-copied, hash-identical public image.
ini_set('zend.exception_ignore_args', '1');
try {
    $preview = '/home/u304629909/domains/preview.tinggaljalan.com';
    $production = '/home/u304629909/domains/tinggaljalan.com';
    require $preview.'/deployments/current/vendor/autoload.php';
    if (trim(file_get_contents($preview.'/.tinggaljalan-staging')) !== 'preview.tinggaljalan.com') {
        throw new RuntimeException;
    }
    $lock = fopen($preview.'/deployments/.lock', 'c');
    if (! flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException;
    }
    $connections = [];
    foreach (['production' => $production, 'preview' => $preview] as $label => $root) {
        $env = Dotenv\Dotenv::parse(file_get_contents($root.'/deployments/shared/.env'));
        $expected = $label === 'preview' ? 'u304629909_tj_preview' : 'u304629909_tinggaljalan';
        if (($env['DB_DATABASE'] ?? '') !== $expected || ! in_array($env['DB_HOST'] ?? '', ['localhost', '127.0.0.1'], true) || ! empty($env['DB_URL'])) {
            throw new RuntimeException;
        }
        $connections[$label] = new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$expected, $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    $source = $connections['production'];
    $source->exec('SET TRANSACTION READ ONLY');
    $source->beginTransaction();
    $rows = $source->query('SELECT id, logo_url FROM site_settings ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) !== 1) {
        throw new RuntimeException;
    }
    $row = $rows[0];
    $relative = preg_replace('~^/?storage/~', '', $row['logo_url']);
    if (! preg_match('~^admin/site/[a-zA-Z0-9_-]+\.(png|jpg|jpeg|webp)$~', $relative)) {
        throw new RuntimeException;
    }
    $public = $production.'/deployments/shared/storage/app/public';
    $file = $public.'/'.$relative;
    if (realpath($file) !== $file || ! is_file($file) || ! getimagesize($file)) {
        throw new RuntimeException;
    }
    $hash = hash_file('sha256', $file);
    $target = 'admin/preview-refresh/'.$hash.'.'.strtolower(pathinfo($relative, PATHINFO_EXTENSION));
    $copied = $preview.'/deployments/shared/storage/app/public/'.$target;
    if (realpath($copied) !== $copied || ! is_file($copied) || hash_file('sha256', $copied) !== $hash) {
        throw new RuntimeException;
    }
    $source->rollBack();
    $db = $connections['preview'];
    $db->beginTransaction();
    $previous = $db->query('SELECT id, logo_url FROM site_settings ORDER BY id FOR UPDATE')->fetchAll(PDO::FETCH_ASSOC);
    if (count($previous) !== 1 || $previous[0]['id'] !== $row['id']) {
        throw new RuntimeException;
    }
    $backup = $preview.'/deployments/shared/.logo-before-repair-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(4)).'.json';
    $oldMask = umask(0077);
    if (file_put_contents($backup, json_encode($previous, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
        throw new RuntimeException;
    }
    umask($oldMask);
    $db->prepare('UPDATE site_settings SET logo_url = ? WHERE id = ?')->execute([$target, $row['id']]);
    $db->commit();
    echo "Preview logo restored to existing copied image; production read-only; previous preview value backed up privately.\n";
    echo 'Logo content SHA256: '.$hash."\n";
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Logo repair refused or failed; no secrets logged.\n");
    exit(1);
}
