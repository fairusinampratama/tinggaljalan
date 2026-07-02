<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'whatsapp_country_code')
            && ! Schema::hasColumn('bookings', 'whatsapp_country')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->renameColumn('whatsapp_country_code', 'whatsapp_country');
            });
        }

        if (! Schema::hasColumn('bookings', 'whatsapp_country')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->string('whatsapp_country', 2)->nullable()->after('whatsapp');
            });
        }

        DB::table('bookings')
            ->where('communication_language', 'en')
            ->update(['communication_language' => 'us']);

        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('communication_language', 2)->default('us')->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')
            ->where('communication_language', 'us')
            ->update(['communication_language' => 'en']);

        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('communication_language', 2)->default('en')->change();
        });

        if (Schema::hasColumn('bookings', 'whatsapp_country')
            && ! Schema::hasColumn('bookings', 'whatsapp_country_code')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->renameColumn('whatsapp_country', 'whatsapp_country_code');
            });
        }
    }
};
