<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('whatsapp_enabled')->default(true);
            $table->string('admin_whatsapp_number')->nullable();
            $table->boolean('email_enabled')->default(true);
            $table->string('admin_email')->nullable();
            $table->timestamps();
        });

        DB::table('notification_settings')->insert([
            'is_enabled' => true,
            'whatsapp_enabled' => true,
            'email_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};