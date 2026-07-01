<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->where('status', 'contacted')
            ->where(function ($query): void {
                $query->whereNotNull('confirmed_at')
                    ->orWhereExists(function ($payments): void {
                        $payments->selectRaw('1')
                            ->from('booking_payments')
                            ->whereColumn('booking_payments.booking_id', 'bookings.id');
                    });
            })
            ->update([
                'status' => 'confirmed',
                'confirmed_at' => DB::raw('COALESCE(confirmed_at, contacted_at, updated_at)'),
            ]);

        DB::table('bookings')->where('status', 'contacted')->update(['status' => 'new']);

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('contacted_at')->nullable()->after('status');
        });
    }
};