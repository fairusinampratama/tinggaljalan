<?php

// Inspect hosting with SELECT-only transactions; emit public counts/digests, never credentials.
ini_set('zend.exception_ignore_args', '1');
try {
    $root = '/home/u304629909/domains/preview.tinggaljalan.com';
    $productionRoot = '/home/u304629909/domains/tinggaljalan.com';
    $revision = $argv[1] ?? '';
    if (! preg_match('/^[a-f0-9]{40}$/', $revision)
        || realpath($root.'/deployments/current') !== $root.'/deployments/releases/'.$revision
        || trim(file_get_contents($root.'/.tinggaljalan-staging')) !== 'preview.tinggaljalan.com') {
        throw new RuntimeException('Unexpected preview revision or root.');
    }
    $release = $root.'/deployments/releases/'.$revision;
    require $release.'/vendor/autoload.php';
    $preview = Dotenv\Dotenv::parse(file_get_contents($root.'/deployments/shared/.env'));
    $production = Dotenv\Dotenv::parse(file_get_contents($productionRoot.'/deployments/shared/.env'));
    if (($preview['APP_ENV'] ?? '') !== 'staging' || ($preview['DB_DATABASE'] ?? '') !== 'u304629909_tj_preview'
        || ($preview['DB_USERNAME'] ?? '') !== 'u304629909_tj_preview' || ! empty($preview['DB_URL'])
        || ($production['APP_ENV'] ?? '') !== 'production'
        || ($production['DB_DATABASE'] ?? '') === $preview['DB_DATABASE']
        || ($production['DB_USERNAME'] ?? '') === $preview['DB_USERNAME']) {
        throw new RuntimeException('Database isolation configuration failed.');
    }
    foreach (['MIDTRANS_SERVER_KEY', 'MIDTRANS_CLIENT_KEY', 'DOKU_SECRET_KEY', 'DOKU_CLIENT_ID', 'SMTP_PASSWORD', 'WHATSPIE_API_TOKEN'] as $key) {
        if (! empty($preview[$key])) {
            throw new RuntimeException('An outbound preview credential is configured.');
        }
    }
    $connect = static function (array $env): PDO {
        $pdo = new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$env['DB_DATABASE'].';charset=utf8mb4', $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec('SET TRANSACTION READ ONLY');
        $pdo->beginTransaction();

        return $pdo;
    };
    $pdo = $connect($preview);
    $live = $connect($production);
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== $preview['DB_DATABASE']
        || $live->query('SELECT DATABASE()')->fetchColumn() !== $production['DB_DATABASE']) {
        throw new RuntimeException('Effective database identity failed.');
    }
    foreach (['email_gateway_settings', 'whatsapp_gateway_settings', 'notification_settings', 'payment_settings'] as $table) {
        if (! $pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()
            || $pdo->query('SELECT COUNT(*) FROM '.$table.' WHERE is_enabled = 1')->fetchColumn()) {
            throw new RuntimeException('Preview integration safety failed.');
        }
    }
    if ($pdo->query('SELECT COUNT(*) FROM payment_settings WHERE public_key IS NOT NULL OR secret_key IS NOT NULL')->fetchColumn()
        || $pdo->query('SELECT COUNT(*) FROM notification_settings WHERE email_enabled = 1 OR whatsapp_enabled = 1')->fetchColumn()) {
        throw new RuntimeException('Preview payment or messaging safety failed.');
    }
    $result = ['revision' => $revision, 'isolated_database_and_user' => true, 'outbound_integrations_disabled' => true,
        'seed_marker_present' => is_file($root.'/deployments/shared/.seeded'), 'public_content' => []];
    foreach (['site_settings', 'hero_slides', 'reviews', 'tour_packages', 'news_articles', 'destinations', 'about_pages'] as $table) {
        $rows = $pdo->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll();
        $result['public_content'][$table] = ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }
    $result['production_public_content'] = [];
    foreach (['site_settings', 'hero_slides', 'reviews', 'tour_packages', 'news_articles', 'destinations', 'about_pages'] as $table) {
        $rows = $live->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll();
        $result['production_public_content'][$table] = ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }
    $result['logo_url'] = $pdo->query('SELECT logo_url FROM site_settings ORDER BY id LIMIT 1')->fetchColumn();
    $result['production_logo_url'] = $live->query('SELECT logo_url FROM site_settings ORDER BY id LIMIT 1')->fetchColumn();
    $applied = $live->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    $required = array_map(static fn ($file) => basename($file, '.php'), glob($release.'/database/migrations/*.php'));
    $result['production_pending_migrations'] = array_values(array_diff($required, $applied));
    if ($result['production_pending_migrations']) {
        throw new RuntimeException('Production has pending database migrations; release requires review.');
    }
    $pdo->rollBack();
    $live->rollBack();
    $media = [];
    $directory = $root.'/deployments/shared/storage/app/public';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && ! $file->isLink()) {
            $media[substr($file->getPathname(), strlen($directory))] = hash_file('sha256', $file->getPathname());
        }
    }
    ksort($media);
    $result['media'] = ['files' => count($media), 'sha256' => hash('sha256', json_encode($media, JSON_THROW_ON_ERROR))];
    $media = [];
    $directory = $productionRoot.'/deployments/shared/storage/app/public';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && ! $file->isLink()) {
            $media[substr($file->getPathname(), strlen($directory))] = hash_file('sha256', $file->getPathname());
        }
    }
    ksort($media);
    $result['production_media'] = ['files' => count($media), 'sha256' => hash('sha256', json_encode($media, JSON_THROW_ON_ERROR))];
    $result['production_revision'] = basename(realpath($productionRoot.'/deployments/current'));
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR, "Read-only preservation verification failed. Credentials and row contents were not logged.\n");
    exit(1);
}
