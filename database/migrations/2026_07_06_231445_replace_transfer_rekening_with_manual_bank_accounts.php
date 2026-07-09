<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->json('manual_bank_accounts')->nullable()->after('secret_key');
            $table->dropColumn('transfer_rekening');
        });

        // Seed default structured data
        DB::table('payment_settings')->where('gateway', 'manual')->update([
            'manual_bank_accounts' => json_encode([
                [
                    'bank_name' => 'Bank BCA',
                    'account_name' => 'PT Tinggal Jalan',
                    'account_number' => '1234567890',
                ]
            ])
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->text('transfer_rekening')->nullable()->after('secret_key');
            $table->dropColumn('manual_bank_accounts');
        });
    }
};
