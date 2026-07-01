<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_gateway_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->default('manual');
            $table->boolean('is_enabled')->default(true);
            $table->string('api_base_url')->nullable();
            $table->text('api_token')->nullable();
            $table->string('session_id')->nullable();
            $table->string('default_country_code')->default('62');
            $table->unsignedInteger('timeout_seconds')->default(15);
            $table->boolean('manual_fallback_enabled')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_test_message')->nullable();
            $table->timestamps();
        });

        DB::table('whatsapp_gateway_settings')->insert([
            'provider' => 'manual',
            'is_enabled' => true,
            'default_country_code' => '62',
            'timeout_seconds' => 15,
            'manual_fallback_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_gateway_settings');
    }
};