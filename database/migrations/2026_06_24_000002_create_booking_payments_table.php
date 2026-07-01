<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('midtrans');
            $table->string('order_id')->unique();
            $table->string('public_token')->unique();
            $table->string('quote_currency', 3);
            $table->unsignedBigInteger('quote_amount');
            $table->string('charge_currency', 3)->default('IDR');
            $table->unsignedInteger('exchange_rate')->nullable();
            $table->json('exchange_rate_snapshot')->nullable();
            $table->unsignedBigInteger('charge_amount');
            $table->string('status')->default('pending');
            $table->string('snap_token')->nullable();
            $table->text('snap_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('whatsapp_opened_at')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->timestamp('whatsapp_failed_at')->nullable();
            $table->text('whatsapp_error')->nullable();
            $table->string('whatsapp_provider_message_id')->nullable();
            $table->json('whatsapp_raw_response')->nullable();
            $table->timestamp('receipt_notifications_attempted_at')->nullable();
            $table->timestamp('receipt_email_sent_at')->nullable();
            $table->timestamp('receipt_email_failed_at')->nullable();
            $table->text('receipt_email_error')->nullable();
            $table->timestamp('receipt_whatsapp_sent_at')->nullable();
            $table->timestamp('receipt_whatsapp_opened_at')->nullable();
            $table->timestamp('receipt_whatsapp_failed_at')->nullable();
            $table->text('receipt_whatsapp_error')->nullable();
            $table->string('receipt_whatsapp_provider_message_id')->nullable();
            $table->json('receipt_whatsapp_raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_payment_type')->nullable();
            $table->string('midtrans_transaction_status')->nullable();
            $table->string('midtrans_fraud_status')->nullable();
            $table->json('midtrans_raw_response')->nullable();
            $table->json('midtrans_raw_notification')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index('public_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
