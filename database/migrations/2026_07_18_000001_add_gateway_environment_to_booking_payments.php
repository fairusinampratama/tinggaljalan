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
            $table->string('gateway_environment', 20)->nullable()->after('provider');
        });

        DB::table('booking_payments')
            ->where('provider', 'doku')
            ->whereNotNull('snap_url')
            ->update([
                'gateway_environment' => DB::raw("CASE WHEN snap_url LIKE '%sandbox.%' OR snap_url LIKE '%staging.%' THEN 'sandbox' ELSE 'production' END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table): void {
            $table->dropColumn('gateway_environment');
        });
    }
};
