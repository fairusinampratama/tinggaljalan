<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->timestamp('whatsapp_opened_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->dropColumn('whatsapp_opened_at');
        });
    }
};