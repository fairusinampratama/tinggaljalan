<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PreviewOutboundSafetyTest extends TestCase
{
    public function test_staging_blocks_an_unconfigured_outbound_http_integration(): void
    {
        $this->app['env'] = 'staging';
        Http::swap(new Factory);
        (new AppServiceProvider($this->app))->boot();

        $this->expectException(StrayRequestException::class);
        Http::get('https://must-not-contact.example.invalid');
    }

    public function test_production_does_not_install_the_preview_http_block(): void
    {
        $this->app['env'] = 'production';
        Http::spy();
        (new AppServiceProvider($this->app))->boot();

        Http::shouldNotHaveReceived('preventStrayRequests');
    }
}
