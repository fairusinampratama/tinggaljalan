<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('contacted_at')->nullable()->after('status');
            $table->timestamp('confirmed_at')->nullable()->after('contacted_at');
            $table->timestamp('cancelled_at')->nullable()->after('confirmed_at');
            $table->timestamp('completed_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'contacted_at',
                'confirmed_at',
                'cancelled_at',
                'completed_at',
            ]);
        });
    }
};
