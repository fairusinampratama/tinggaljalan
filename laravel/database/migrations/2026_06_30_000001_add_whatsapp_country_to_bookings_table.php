<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('whatsapp_country', 2)->nullable()->after('whatsapp');
        });

        DB::table('bookings')
            ->select(['id', 'whatsapp'])
            ->whereNotNull('whatsapp')
            ->orderBy('id')
            ->chunkById(100, function ($bookings): void {
                foreach ($bookings as $booking) {
                    $country = PhoneNumber::detectCountry($booking->whatsapp);

                    if ($country) {
                        DB::table('bookings')->where('id', $booking->id)->update([
                            'whatsapp_country' => $country,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('whatsapp_country');
        });
    }
};
