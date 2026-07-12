<?php

namespace Tests\Feature;

use App\Gateways\WhatsApp\WhatspieClient;
use App\Jobs\SendNewBookingAdminNotification;
use App\Mail\NewBookingAdminMail;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\NotificationSetting;
use App\Models\WhatsappGatewaySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\FakeWhatspieClient;
use Tests\TestCase;

class NewBookingAdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifier_sends_email_notification_when_email_channel_is_enabled(): void
    {
        Mail::fake();
        NotificationSetting::current()->update([
            'is_enabled' => true,
            'whatsapp_enabled' => false,
            'email_enabled' => true,
            'admin_email' => 'owner@example.test',
        ]);
        $booking = $this->booking(['booking_code' => 'TJ-ADMIN-MAIL']);

        $this->runNotifier($booking);

        Mail::assertSent(NewBookingAdminMail::class, fn (NewBookingAdminMail $mail): bool =>
            $mail->hasTo('owner@example.test') && $mail->booking->is($booking)
        );
        $booking->refresh();
        $this->assertNotNull($booking->admin_notification_attempted_at);
        $this->assertNotNull($booking->admin_email_sent_at);
        $this->assertNull($booking->admin_email_failed_at);
    }

    public function test_notifier_sends_whatsapp_notification_when_automatic_gateway_is_enabled(): void
    {
        $fake = new FakeWhatspieClient();
        $this->app->instance(WhatspieClient::class, $fake);
        WhatsappGatewaySetting::current()->update([
            'provider' => WhatsappGatewaySetting::PROVIDER_WHATSPIE,
            'is_enabled' => true,
            'default_country_code' => '62',
        ]);
        NotificationSetting::current()->update([
            'is_enabled' => true,
            'whatsapp_enabled' => true,
            'admin_whatsapp_number' => '081234567890',
            'email_enabled' => false,
        ]);
        $booking = $this->booking(['booking_code' => 'TJ-ADMIN-WA']);

        $this->runNotifier($booking);

        $this->assertCount(1, $fake->sent);
        $this->assertSame('6281234567890', $fake->sent[0]['to']);
        $this->assertStringContainsString('TJ-ADMIN-WA', $fake->sent[0]['message']);
        $booking->refresh();
        $this->assertNotNull($booking->admin_whatsapp_sent_at);
        $this->assertNull($booking->admin_whatsapp_failed_at);
    }

    public function test_whatsapp_failure_does_not_throw_and_records_failure(): void
    {
        $fake = new FakeWhatspieClient();
        $fake->exception = new \RuntimeException('Whatspie is down');
        $this->app->instance(WhatspieClient::class, $fake);
        WhatsappGatewaySetting::current()->update([
            'provider' => WhatsappGatewaySetting::PROVIDER_WHATSPIE,
            'is_enabled' => true,
            'default_country_code' => '62',
        ]);
        NotificationSetting::current()->update([
            'is_enabled' => true,
            'whatsapp_enabled' => true,
            'admin_whatsapp_number' => '+6281234567890',
            'email_enabled' => false,
        ]);
        $booking = $this->booking();

        $this->runNotifier($booking);

        $booking->refresh();
        $this->assertNotNull($booking->admin_notification_attempted_at);
        $this->assertNotNull($booking->admin_whatsapp_failed_at);
        $this->assertNull($booking->admin_whatsapp_sent_at);
        $this->assertStringContainsString('Whatspie is down', $booking->admin_notification_error);
    }

    public function test_disabled_settings_send_nothing_and_leave_booking_unmarked(): void
    {
        Mail::fake();
        $fake = new FakeWhatspieClient();
        $this->app->instance(WhatspieClient::class, $fake);
        NotificationSetting::current()->update([
            'is_enabled' => false,
            'whatsapp_enabled' => true,
            'admin_whatsapp_number' => '+6281234567890',
            'email_enabled' => true,
            'admin_email' => 'owner@example.test',
        ]);
        $booking = $this->booking();

        $this->runNotifier($booking);

        Mail::assertNothingSent();
        $this->assertSame([], $fake->sent);
        $this->assertNull($booking->refresh()->admin_notification_attempted_at);
    }

    public function test_email_only_and_whatsapp_only_modes_work_independently(): void
    {
        Mail::fake();
        $fake = new FakeWhatspieClient();
        $this->app->instance(WhatspieClient::class, $fake);
        WhatsappGatewaySetting::current()->update([
            'provider' => WhatsappGatewaySetting::PROVIDER_WHATSPIE,
            'is_enabled' => true,
            'default_country_code' => '62',
        ]);

        NotificationSetting::current()->update([
            'is_enabled' => true,
            'whatsapp_enabled' => false,
            'admin_whatsapp_number' => '+6281234567890',
            'email_enabled' => true,
            'admin_email' => 'owner@example.test',
        ]);
        $this->runNotifier($this->booking(['booking_code' => 'TJ-EMAIL-ONLY']));

        NotificationSetting::current()->update([
            'whatsapp_enabled' => true,
            'email_enabled' => false,
        ]);
        $this->runNotifier($this->booking(['booking_code' => 'TJ-WA-ONLY']));

        Mail::assertSent(NewBookingAdminMail::class, 1);
        $this->assertCount(1, $fake->sent);
        $this->assertStringContainsString('TJ-WA-ONLY', $fake->sent[0]['message']);
    }

    private function runNotifier(Booking $booking): void
    {
        app(SendNewBookingAdminNotification::class, ['bookingId' => $booking->id])->handle(
            app(\App\Gateways\Email\EmailGatewayService::class),
            app(\App\Gateways\WhatsApp\WhatsAppGatewayService::class),
            app(\App\Gateways\WhatsApp\NewBookingAdminWhatsAppMessage::class),
        );
    }

    private function booking(array $overrides = []): Booking
    {
        $destination = Destination::firstOrCreate([
            'slug' => 'bromo',
        ], [
            'name' => 'Bromo',
        ]);
        $package = \App\Models\TourPackage::firstOrCreate([
            'slug' => 'bromo-sunrise',
        ], [
            'destination_id' => $destination->id,
            'title' => ['us' => 'Bromo Sunrise'],
            'base_price_idr' => 500000,
            'base_price_usd' => 35,
            'is_active' => true,
        ]);

        return Booking::create(array_merge([
            'booking_code' => 'TJ-ADMIN-'.strtoupper(fake()->bothify('????')),
            'tour_package_id' => $package->id,
            'destination_id' => $destination->id,
            'name' => 'Admin Notification Test',
            'email' => 'traveler@example.test',
            'whatsapp' => '+628111111111',
            'communication_language' => 'us',
            'travel_date' => now()->addWeeks(3)->toDateString(),
            'pax' => 2,
            'pickup' => 'Malang Hotel',
            'traveler_type' => 'international',
            'currency' => 'IDR',
            'subtotal' => 500000,
            'discount_total' => 0,
            'total' => 500000,
            'status' => 'new',
        ], $overrides));
    }
}
