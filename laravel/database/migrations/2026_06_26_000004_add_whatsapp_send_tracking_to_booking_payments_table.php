<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_opened_at');
            $table->timestamp('whatsapp_failed_at')->nullable()->after('whatsapp_sent_at');
            $table->text('whatsapp_error')->nullable()->after('whatsapp_failed_at');
            $table->string('whatsapp_provider_message_id')->nullable()->after('whatsapp_error');
            $table->json('whatsapp_raw_response')->nullable()->after('whatsapp_provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->dropColumn([
                'whatsapp_sent_at',
                'whatsapp_failed_at',
                'whatsapp_error',
                'whatsapp_provider_message_id',
                'whatsapp_raw_response',
            ]);
        });
    }
};