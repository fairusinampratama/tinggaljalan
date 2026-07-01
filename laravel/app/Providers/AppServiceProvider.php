<?php

namespace App\Providers;

use App\Gateways\WhatsApp\HttpWhatspieClient;
use App\Gateways\WhatsApp\WhatspieClient;
use App\Payments\ExchangeRates\ExchangeRateClient;
use App\Payments\ExchangeRates\FrankfurterExchangeRateClient;
use App\Payments\Midtrans\HttpMidtransClient;
use App\Payments\Midtrans\MidtransClient;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MidtransClient::class, HttpMidtransClient::class);
        $this->app->bind(ExchangeRateClient::class, FrankfurterExchangeRateClient::class);
        $this->app->bind(WhatspieClient::class, HttpWhatspieClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The public tunnel uses HTTPS, while local Docker remains HTTP-only.
        if (! app()->runningInConsole() && request()->isSecure()) {
            URL::forceScheme('https');
        }
    }
}
