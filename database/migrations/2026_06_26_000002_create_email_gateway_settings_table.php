<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_gateway_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->default('log');
            $table->boolean('is_enabled')->default(true);
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('scheme')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_test_message')->nullable();
            $table->timestamps();
        });

        DB::table('email_gateway_settings')->insert([
            'provider' => 'log',
            'is_enabled' => true,
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'scheme' => 'smtp',
            'from_address' => 'booking@tinggaljalan.com',
            'from_name' => 'Tinggal Jalan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_gateway_settings');
    }
};