<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->string('mode')->default('sandbox');
            $table->text('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('public_label')->default('Secure Midtrans payment link');
            $table->text('booking_note')->nullable();
            $table->text('usd_note')->nullable();
            $table->string('exchange_rate_provider')->default('frankfurter');
            $table->decimal('exchange_rate_buffer_percent', 5, 2)->default(2);
            $table->unsignedInteger('exchange_rate_cache_ttl_hours')->default(12);
            $table->timestamps();
        });

        DB::table('payment_settings')->insert([
            'gateway' => 'midtrans',
            'is_enabled' => true,
            'mode' => 'sandbox',
            'public_label' => 'Secure Midtrans payment link',
            'booking_note' => 'Payment is requested only after our team confirms availability.',
            'usd_note' => 'USD quotes are converted to IDR when payment is requested. Midtrans processes payments in IDR.',
            'exchange_rate_provider' => 'frankfurter',
            'exchange_rate_buffer_percent' => 2,
            'exchange_rate_cache_ttl_hours' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};