<?php

use App\Models\EmailGatewaySetting;
use App\Models\NotificationSetting;
use App\Models\PaymentSetting;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WhatsappGatewaySetting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

ini_set('zend.exception_ignore_args', '1');
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, 'Staging configuration failed ('.get_class($error)."). Credentials were not logged.\n");
    exit(1);
});

// Host-side helper. Never prints credentials or copies production configuration.
require $argv[1].'/vendor/autoload.php';

$release = realpath($argv[1]);
$root = $argv[2];
$mode = $argv[3];
if (! preg_match('~^/home/[a-zA-Z0-9_-]+/domains/preview\.tinggaljalan\.com$~', $root)
    || realpath($root) !== $root
    || trim(file_get_contents($root.'/.tinggaljalan-staging')) !== 'preview.tinggaljalan.com'
    || ! str_starts_with($release, $root.'/deployments/releases/')) {
    throw new RuntimeException('Unexpected staging layout.');
}
$shared = $root.'/deployments/shared';
$input = json_decode(file_get_contents($argv[4]), true, flags: JSON_THROW_ON_ERROR);
foreach (['db_password', 'review_password', 'admin_password'] as $field) {
    if (! is_string($input[$field] ?? null) || strlen($input[$field]) < 16
        || preg_match('/[\x00-\x1f\x7f]/', $input[$field])) {
        throw new RuntimeException('Missing or invalid staging password: '.$field);
    }
}
if (count(array_unique(array_values(array_intersect_key($input, array_flip(['db_password', 'review_password', 'admin_password']))))) !== 3) {
    throw new RuntimeException('Use three different staging passwords.');
}

if ($mode === 'configure') {
    $envPath = $shared.'/.env';
    if (! file_exists($envPath)) {
        $values = Dotenv\Dotenv::parse(file_get_contents($release.'/.env.staging.example'));
        $values['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
        $values['DB_PASSWORD'] = $input['db_password'];
        $lines = [];
        foreach ($values as $name => $value) {
            $quoted = '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
            $roundtrip = Dotenv\Dotenv::parse($name.'='.$quoted);
            if ($roundtrip[$name] !== $value) {
                throw new RuntimeException('Configuration value could not be safely encoded.');
            }
            $lines[] = $name.'='.$quoted;
        }
        file_put_contents($envPath, implode("\n", $lines)."\n", LOCK_EX);
        chmod($envPath, 0600);
    }
    $values = Dotenv\Dotenv::parse(file_get_contents($envPath));
    foreach (['APP_ENV' => 'staging', 'APP_DEBUG' => 'false', 'APP_URL' => 'https://preview.tinggaljalan.com',
        'DB_CONNECTION' => 'mysql', 'DB_HOST' => 'localhost', 'DB_DATABASE' => 'u304629909_tj_preview',
        'DB_USERNAME' => 'u304629909_tj_preview', 'DB_URL' => '', 'MAIL_MAILER' => 'log',
        'GOOGLE_ADS_ID' => '', 'GOOGLE_ADS_CONSENT_ENABLED' => 'false', 'QUEUE_CONNECTION' => 'database',
        'MIDTRANS_SERVER_KEY' => '', 'MIDTRANS_CLIENT_KEY' => '', 'DOKU_SECRET_KEY' => '',
        'DOKU_CLIENT_ID' => '', 'SMTP_PASSWORD' => '', 'WHATSPIE_API_TOKEN' => ''] as $name => $expected) {
        if (($values[$name] ?? null) !== $expected) {
            throw new RuntimeException('Unsafe staging configuration: '.$name);
        }
    }
    if (($values['DB_PASSWORD'] ?? '') !== $input['db_password'] || empty($values['APP_KEY'])) {
        throw new RuntimeException('Existing staging credentials differ; rotation requires a separate operation.');
    }
    // Check the selected schema before migrations; never probe production schemas.
    $pdo = new PDO('mysql:host=localhost;dbname=u304629909_tj_preview', 'u304629909_tj_preview', $input['db_password']);
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'u304629909_tj_preview') {
        throw new RuntimeException('Unexpected connected schema.');
    }
    $auth = $shared.'/.htpasswd';
    if (! file_exists($auth)) {
        file_put_contents($auth, 'reviewer:'.password_hash($input['review_password'], PASSWORD_BCRYPT)."\n", LOCK_EX);
        chmod($auth, 0644);
    } elseif (! password_verify($input['review_password'], trim(substr(file_get_contents($auth), strlen('reviewer:'))))) {
        throw new RuntimeException('Existing preview access password differs.');
    }
    exit;
}
if ($mode !== 'initialize') {
    throw new RuntimeException('Unknown staging configuration operation.');
}
chdir($release);
$app = require $release.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('app.env') !== 'staging' || config('database.connections.mysql.database') !== 'u304629909_tj_preview') {
    throw new RuntimeException('Unexpected effective staging configuration.');
}
// Seed curated example content without DatabaseSeeder's default admin/integrations.
if (! file_exists($shared.'/.seeded')) {
    foreach (['DestinationSeeder', 'RouteFilterSeeder', 'TourPackageSeeder', 'NewsSeeder', 'FaqSeeder',
        'HomeContentSeeder', 'HeroSlideSeeder', 'BookingOptionSeeder', 'PlatformLinkSeeder', 'SiteSettingSeeder',
        'ReviewSeeder', 'AboutPageSeeder', 'TeamMemberSeeder', 'CompanyMilestoneSeeder'] as $seeder) {
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\'.$seeder, '--force' => true]);
    }
    file_put_contents($shared.'/.seeded', "synthetic-content\n");
}
EmailGatewaySetting::current()->update(['is_enabled' => false, 'provider' => 'log', 'password' => null]);
WhatsappGatewaySetting::current()->update(['is_enabled' => false, 'api_token' => null]);
NotificationSetting::current()->update(['is_enabled' => false, 'email_enabled' => false, 'whatsapp_enabled' => false]);
PaymentSetting::midtrans()->update(['is_enabled' => false, 'mode' => 'sandbox', 'public_key' => null, 'secret_key' => null]);
PaymentSetting::doku()->update(['is_enabled' => false, 'mode' => 'sandbox', 'public_key' => null, 'secret_key' => null]);
PaymentSetting::query()->update(['is_enabled' => false, 'mode' => 'sandbox', 'public_key' => null, 'secret_key' => null]);
SiteSetting::query()->update(['logo_url' => '/images/logo-tj.png']);
$admin = User::firstOrNew(['email' => 'preview-admin@tinggaljalan.test']);
$admin->forceFill(['name' => 'Preview Admin', 'password' => Hash::make($input['admin_password']), 'is_admin' => true])->save();
if (User::where('email', 'admin@tinggaljalan.test')->exists()) {
    throw new RuntimeException('Default development admin exists; review the preview database before deployment.');
}
echo "Staging database connected; curated content and disabled integrations prepared.\n";
