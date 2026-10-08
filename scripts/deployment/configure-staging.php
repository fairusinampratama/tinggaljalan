<?php

use App\Models\EmailGatewaySetting;
use App\Models\NotificationSetting;
use App\Models\PaymentSetting;
use App\Models\User;
use App\Models\WhatsappGatewaySetting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

ini_set('zend.exception_ignore_args', '1');
set_exception_handler(function (Throwable $error): void {
    $reason = $error instanceof StagingConfigurationException ? $error->getMessage() : 'Unexpected configuration failure ('.get_class($error).').';
    fwrite(STDERR, $reason." Credentials were not logged.\n");
    exit(1);
});

// Host-side helper. Never prints credentials or copies production configuration.
require $argv[1].'/vendor/autoload.php';
require __DIR__.'/staging-config.php';

$release = realpath($argv[1]);
$root = $argv[2];
$mode = $argv[3];
if (! preg_match('~^/home/[a-zA-Z0-9_-]+/domains/preview\.tinggaljalan\.com$~', $root)
    || realpath($root) !== $root
    || trim(file_get_contents($root.'/.tinggaljalan-staging')) !== 'preview.tinggaljalan.com'
    || ! str_starts_with($release, $root.'/deployments/releases/')) {
    throw new StagingConfigurationException('Unexpected staging layout.');
}
$shared = $root.'/deployments/shared';
$input = json_decode(file_get_contents($argv[4]), true, flags: JSON_THROW_ON_ERROR);
stagingValidatePasswords($input);

if ($mode === 'configure') {
    stagingConfigure($shared, $release.'/.env.staging.example', $input, function (string $password): string {
        $pdo = new PDO('mysql:host=localhost;dbname=u304629909_tj_preview', 'u304629909_tj_preview', $password);

        return $pdo->query('SELECT DATABASE()')->fetchColumn();
    });
    exit;
}
if ($mode !== 'initialize') {
    throw new StagingConfigurationException('Unknown staging configuration operation.');
}
chdir($release);
$app = require $release.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('app.env') !== 'staging' || config('database.connections.mysql.database') !== 'u304629909_tj_preview') {
    throw new StagingConfigurationException('Unexpected effective staging configuration.');
}
// Seed curated example content without DatabaseSeeder's default admin/integrations.
if (! file_exists($shared.'/.seeded')) {
    // A missing marker must never authorize replacing refreshed or edited content.
    foreach (['destinations', 'route_filters', 'tour_packages', 'news_articles', 'faqs',
        'trust_stats', 'why_choose_items', 'hero_slides', 'vouchers', 'package_availabilities', 'platform_links', 'site_settings',
        'reviews', 'about_pages', 'team_members', 'company_milestones'] as $table) {
        if (DB::table($table)->exists()) {
            throw new StagingConfigurationException('Preview content exists but its initialization marker is missing; refusing to reseed.');
        }
    }
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
// Preserve existing public branding. The frontend supplies its own fallback.
$admin = User::firstOrNew(['email' => 'preview-admin@tinggaljalan.test']);
$admin->forceFill(['name' => 'Preview Admin', 'password' => Hash::make($input['admin_password']), 'is_admin' => true])->save();
if (User::where('email', 'admin@tinggaljalan.test')->exists()) {
    throw new StagingConfigurationException('Default development admin exists; review the preview database before deployment.');
}
echo "Staging database connected; curated content and disabled integrations prepared.\n";
