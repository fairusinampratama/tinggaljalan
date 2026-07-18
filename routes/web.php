<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingPaymentWhatsappController;
use App\Http\Controllers\CheckoutPaymentController;
use App\Http\Controllers\DokuWebhookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SitemapController;
use App\Support\InertiaPublicData;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/about-us', AboutController::class)->name('about.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(
    "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /booking\nDisallow: /checkout/\n\nSitemap: https://tinggaljalan.com/sitemap.xml\n",
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8']
))->name('robots');
Route::get('/language/{language}', LanguageController::class)->name('language');

Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
Route::get('/routes/{slug}', [RouteController::class, 'show'])->name('routes.show');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
Route::post('/booking/recalculate', [BookingController::class, 'recalculate'])->name('booking.recalculate');
Route::post('/booking', [BookingController::class, 'storeDraft'])->name('booking.store');
Route::get('/checkout/review', [BookingController::class, 'review'])->name('checkout.review');
Route::post('/checkout/review', [BookingController::class, 'submit'])->name('checkout.submit');
Route::get('/checkout/payment', [BookingController::class, 'payment'])->name('checkout.payment');
Route::get('/checkout/payment/{payment}', [CheckoutPaymentController::class, 'show'])->name('checkout.payment.show');
Route::get('/checkout/payment/{payment}/status', [CheckoutPaymentController::class, 'status'])
    ->middleware('throttle:20,1')
    ->name('checkout.payment.status');
Route::post('/midtrans/webhook', MidtransWebhookController::class)->name('midtrans.webhook');
Route::post('/doku/webhook', DokuWebhookController::class)->name('doku.webhook');
Route::get('/admin/payment-handoffs/{payment}/whatsapp', BookingPaymentWhatsappController::class)
    ->middleware('auth')
    ->name('admin.booking-payments.whatsapp');
Route::get('/checkout/confirmation', [BookingController::class, 'confirmation'])->name('checkout.confirmation');
Route::get('/test-articles', function () {
    return count(InertiaPublicData::shared(request())['articles']);
});
