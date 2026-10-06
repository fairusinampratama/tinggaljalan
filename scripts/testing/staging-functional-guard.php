<?php

use App\Models\Booking;
use App\Models\EmailGatewaySetting;
use App\Models\NotificationSetting;
use App\Models\PaymentSetting;
use App\Models\WhatsappGatewaySetting;
use App\Payments\BookingPaymentService;
use App\Payments\PaymentSettingsService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// Read-only safety checks; payment calls use an unsaved synthetic model.
ini_set('zend.exception_ignore_args', '1');
$stage = 'layout';
try {
    $root = '/home/u304629909/domains/preview.tinggaljalan.com';
    $revision = $argv[1] ?? '';
    if (! preg_match('/^[a-f0-9]{40}$/', $revision)
        || trim(file_get_contents($root.'/.tinggaljalan-staging')) !== 'preview.tinggaljalan.com'
        || realpath($root.'/deployments/current') !== $root.'/deployments/releases/'.$revision) {
        throw new RuntimeException('Unexpected preview release.');
    }
    $release = $root.'/deployments/releases/'.$revision;
    chdir($release);
    require $release.'/vendor/autoload.php';
    $app = require $release.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    if (config('app.env') !== 'staging' || config('app.url') !== 'https://preview.tinggaljalan.com'
        || DB::selectOne('SELECT DATABASE() AS name')->name !== 'u304629909_tj_preview') {
        throw new RuntimeException('Unexpected preview configuration.');
    }
    $stage = 'integration settings';
    foreach ([EmailGatewaySetting::class, WhatsappGatewaySetting::class,
        NotificationSetting::class, PaymentSetting::class] as $model) {
        if (! $model::query()->exists() || $model::query()->where('is_enabled', true)->exists()) {
            throw new RuntimeException('An outbound integration is enabled or missing.');
        }
    }
    if (PaymentSetting::query()->whereNotNull('public_key')->exists()
        || PaymentSetting::query()->whereNotNull('secret_key')->exists()) {
        throw new RuntimeException('Payment credentials exist.');
    }
    Http::preventStrayRequests();
    $settings = app(PaymentSettingsService::class);
    $stage = 'disabled gateway selection';
    if ($settings->midtransEnabled() || $settings->dokuEnabled() || $settings->isManualActive()) {
        throw new RuntimeException('A disabled gateway is treated as active.');
    }
    $stage = 'disabled gateway rejection';
    $booking = new Booking;
    $booking->forceFill(['status' => 'confirmed', 'pricing_status' => 'priced', 'total' => 100000, 'currency' => 'IDR']);
    $service = app(BookingPaymentService::class);
    foreach (['createPaymentRequest', 'createMidtransPaymentRequest', 'createDokuPaymentRequest', 'createManualPaymentRequest'] as $method) {
        try {
            $service->$method($booking);
            throw new RuntimeException('Disabled payment method accepted a request.');
        } catch (InvalidArgumentException $error) {
            if (! str_contains($error->getMessage(), 'payments are disabled')) {
                throw new RuntimeException('Unexpected payment rejection.');
            }
        }
    }
    echo "Preview database isolated; notifications disabled; all payment methods reject requests without network calls.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Preview functional guard failed at '.$stage.'. No credentials logged.'.PHP_EOL);
    exit(1);
}
