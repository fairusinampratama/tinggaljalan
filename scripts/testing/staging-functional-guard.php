<?php
// Read-only safety checks; payment calls use an unsaved synthetic model.
ini_set('zend.exception_ignore_args', '1');
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, 'Preview functional guard failed ('.get_class($error)."). No credentials logged.\n");
    exit(1);
});
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
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('app.env') !== 'staging' || config('app.url') !== 'https://preview.tinggaljalan.com'
    || Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS name')->name !== 'u304629909_tj_preview') {
    throw new RuntimeException('Unexpected preview configuration.');
}
foreach ([App\Models\EmailGatewaySetting::class, App\Models\WhatsappGatewaySetting::class,
    App\Models\NotificationSetting::class, App\Models\PaymentSetting::class] as $model) {
    if (! $model::query()->exists() || $model::query()->where('is_enabled', true)->exists()) {
        throw new RuntimeException('An outbound integration is enabled or missing.');
    }
}
if (App\Models\PaymentSetting::query()->whereNotNull('public_key')->exists()
    || App\Models\PaymentSetting::query()->whereNotNull('secret_key')->exists()) {
    throw new RuntimeException('Payment credentials exist.');
}
Illuminate\Support\Facades\Http::preventStrayRequests();
$booking = new App\Models\Booking;
$booking->forceFill(['status' => 'confirmed', 'pricing_status' => 'priced', 'total' => 100000, 'currency' => 'IDR']);
$service = app(App\Payments\BookingPaymentService::class);
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
