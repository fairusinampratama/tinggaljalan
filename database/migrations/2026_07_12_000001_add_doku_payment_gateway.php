<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->string('doku_request_id')->nullable()->after('midtrans_raw_notification');
            $table->string('doku_payment_token')->nullable()->after('doku_request_id');
            $table->string('doku_transaction_status')->nullable()->after('doku_payment_token');
            $table->json('doku_raw_response')->nullable()->after('doku_transaction_status');
            $table->json('doku_raw_notification')->nullable()->after('doku_raw_response');
        });

        DB::table('payment_settings')->insertOrIgnore([
            'gateway' => 'doku',
            'is_enabled' => false,
            'mode' => 'sandbox',
            'public_label' => 'Secure DOKU payment link',
            'booking_note' => 'You won\'t be charged yet. We will send a secure DOKU payment link once your booking is confirmed.',
            'usd_note' => 'DOKU charges securely in IDR after USD quotes are converted.',
            'exchange_rate_provider' => 'frankfurter',
            'exchange_rate_buffer_percent' => 2,
            'exchange_rate_cache_ttl_hours' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('payment_settings')->where('gateway', 'doku')->delete();

        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->dropColumn([
                'doku_request_id',
                'doku_payment_token',
                'doku_transaction_status',
                'doku_raw_response',
                'doku_raw_notification',
            ]);
        });
    }
};
