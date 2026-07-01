<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->json('exchange_rate_snapshot')->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->dropColumn('exchange_rate_snapshot');
        });
    }
};