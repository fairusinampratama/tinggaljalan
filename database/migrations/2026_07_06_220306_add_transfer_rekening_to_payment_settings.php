<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->text('transfer_rekening')->nullable()->after('secret_key');
        });

        DB::table('payment_settings')->insertOrIgnore([
            'gateway' => 'manual',
            'is_enabled' => false,
            'mode' => 'production',
            'public_label' => 'Manual Bank Transfer',
            'booking_note' => 'Please transfer the exact amount and send the receipt to our WhatsApp.',
            'usd_note' => 'USD quotes are converted to IDR based on today\'s exchange rate.',
            'transfer_rekening' => 'Bank BCA\nAccount Name: PT Tinggal Jalan\nAccount Number: 1234567890',
            'exchange_rate_provider' => 'frankfurter',
            'exchange_rate_buffer_percent' => 2,
            'exchange_rate_cache_ttl_hours' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payment_settings')->where('gateway', 'manual')->delete();
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn('transfer_rekening');
        });
    }
};
