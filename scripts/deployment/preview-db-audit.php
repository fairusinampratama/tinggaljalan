<?php

ini_set('zend.exception_ignore_args', '1');
$stage = 'bootstrap';
try {
    $preview = '/home/u304629909/domains/preview.tinggaljalan.com';
    $production = '/home/u304629909/domains/tinggaljalan.com';
    require $preview.'/deployments/current/vendor/autoload.php';
    foreach (['preview' => $preview, 'production' => $production] as $label => $root) {
        $stage = $label.' environment';
        $env = Dotenv\Dotenv::parse(file_get_contents($root.'/deployments/shared/.env'));
        if (! in_array($env['DB_HOST'] ?? '', ['localhost', '127.0.0.1'], true) || ! empty($env['DB_URL'])) {
            throw new RuntimeException;
        }
        $db = $env['DB_DATABASE'];
        if (! preg_match('/^u304629909_[a-zA-Z0-9_]+$/', $db)) {
            throw new RuntimeException;
        }
        if ($label === 'preview' && $db !== 'u304629909_tj_preview') {
            throw new RuntimeException;
        }
        if ($label === 'production' && $db === 'u304629909_tj_preview') {
            throw new RuntimeException;
        }
        $stage = $label.' connection';
        $pdo = new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$db, $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stage = $label.' read-only snapshot';
        echo $label.' server version: '.$pdo->query('SELECT VERSION()')->fetchColumn()."\n";
        $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->exec('SET TRANSACTION READ ONLY');
        $stage = $label.' start snapshot';
        $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
        echo $label.' schema: '.$pdo->query('SELECT DATABASE()')->fetchColumn()."\n";
        $tables = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
        foreach ($tables as [$table, $type]) {
            if (! preg_match('/^[a-z0-9_]+$/', $table) || $type !== 'BASE TABLE') {
                throw new RuntimeException;
            }
            $columns = $pdo->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC);
            echo $label.' '.$table.' columns: '.implode(',', array_column($columns, 'Field'))."\n";
            echo $label.' '.$table.' rows: '.$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn()."\n";
        }
        if ($label === 'production') {
            $public = $root.'/deployments/shared/storage/app/public';
            echo 'Production public storage canonical: '.(realpath($public) === $public ? 'yes' : 'no')."\n";
            echo 'Usable public tours for default two travelers: '.$pdo->query('SELECT COUNT(*) FROM tour_packages WHERE is_active = 1 AND (base_price_idr > 0 OR EXISTS (SELECT 1 FROM package_price_tiers WHERE tour_package_id = tour_packages.id AND min_pax <= 2 AND (max_pax IS NULL OR max_pax >= 2) AND price_idr > 0))')->fetchColumn()."\n";
            foreach (['hero_slides' => ['desktop_image', 'mobile_image'], 'tour_packages' => ['cover_image', 'gallery'], 'destinations' => ['cover_image'], 'news_articles' => ['cover_image'], 'team_members' => ['portrait'], 'company_milestones' => ['image']] as $table => $fields) {
                foreach ($pdo->query('SELECT '.implode(',', $fields).' FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    foreach ($row as $field => $value) {
                        if ($field === 'gallery') {
                            $values = json_decode($value ?? '[]', true) ?? [];
                        } else {
                            $values = [$value];
                        }
                        array_walk_recursive($values, function ($path) use ($table, $field, $public): void {
                            if (! is_string($path)) {
                                return;
                            }
                            $relative = preg_replace('~^https://(?:www\.)?tinggaljalan\.com/~', '/', $path);
                            $relative = preg_replace('~^(?:/)?(?:storage/|public/)?~', '', $relative);
                            if (! str_starts_with($relative, 'admin/') && ! str_starts_with($relative, 'uploads/')) {
                                return;
                            }
                            // Only explicitly public tourism image references; no customer/auth data.
                            echo 'Public tourism media '.$table.'.'.$field.': '.$relative.'; exists='.(is_file($public.'/'.$relative) ? 'yes' : 'no').'; valid-image='.(is_file($public.'/'.$relative) && getimagesize($public.'/'.$relative) !== false ? 'yes' : 'no')."\n";
                        });
                    }
                }
            }
        }
        $pdo->rollBack();
        echo $label.' revision: '.trim(file_get_contents($root.'/deployments/current/REVISION'))."\n";
    }
    echo "Read-only schema audit completed; no row values or credentials disclosed.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Read-only schema audit failed at '.$stage."; no credentials or row values logged.\n");
    if ($error instanceof PDOException) {
        echo 'Database diagnostic code: '.(int) ($error->errorInfo[1] ?? 0)."\n";
    }
    exit(1);
}
