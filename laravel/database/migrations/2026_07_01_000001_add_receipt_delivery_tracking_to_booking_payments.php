<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->timestamp('receipt_notifications_attempted_at')->nullable()->after('whatsapp_raw_response');
            $table->timestamp('receipt_email_sent_at')->nullable()->after('receipt_notifications_attempted_at');
            $table->timestamp('receipt_email_failed_at')->nullable()->after('receipt_email_sent_at');
            $table->text('receipt_email_error')->nullable()->after('receipt_email_failed_at');
            $table->timestamp('receipt_whatsapp_sent_at')->nullable()->after('receipt_email_error');
            $table->timestamp('receipt_whatsapp_opened_at')->nullable()->after('receipt_whatsapp_sent_at');
            $table->timestamp('receipt_whatsapp_failed_at')->nullable()->after('receipt_whatsapp_opened_at');
            $table->text('receipt_whatsapp_error')->nullable()->after('receipt_whatsapp_failed_at');
            $table->string('receipt_whatsapp_provider_message_id')->nullable()->after('receipt_whatsapp_error');
            $table->json('receipt_whatsapp_raw_response')->nullable()->after('receipt_whatsapp_provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->dropColumn([
                'receipt_notifications_attempted_at',
                'receipt_email_sent_at',
                'receipt_email_failed_at',
                'receipt_email_error',
                'receipt_whatsapp_sent_at',
                'receipt_whatsapp_opened_at',
                'receipt_whatsapp_failed_at',
                'receipt_whatsapp_error',
                'receipt_whatsapp_provider_message_id',
                'receipt_whatsapp_raw_response',
            ]);
        });
    }
};
