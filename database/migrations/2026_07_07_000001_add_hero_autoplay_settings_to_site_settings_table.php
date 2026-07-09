<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('hero_autoplay_enabled')->default(false);
            $table->unsignedInteger('hero_autoplay_interval')->default(8000);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['hero_autoplay_enabled', 'hero_autoplay_interval']);
        });
    }
};
