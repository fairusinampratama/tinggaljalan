<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('admin_notification_attempted_at')->nullable()->after('completed_at');
            $table->timestamp('admin_whatsapp_sent_at')->nullable()->after('admin_notification_attempted_at');
            $table->timestamp('admin_whatsapp_failed_at')->nullable()->after('admin_whatsapp_sent_at');
            $table->timestamp('admin_email_sent_at')->nullable()->after('admin_whatsapp_failed_at');
            $table->timestamp('admin_email_failed_at')->nullable()->after('admin_email_sent_at');
            $table->text('admin_notification_error')->nullable()->after('admin_email_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'admin_notification_attempted_at',
                'admin_whatsapp_sent_at',
                'admin_whatsapp_failed_at',
                'admin_email_sent_at',
                'admin_email_failed_at',
                'admin_notification_error',
            ]);
        });
    }
};