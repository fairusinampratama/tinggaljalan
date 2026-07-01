<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo_url')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('business_address')->nullable();
            $table->string('google_maps_url')->nullable();
            $table->json('service_hours')->nullable();
            $table->json('service_areas')->nullable();
            $table->json('trust_badges')->nullable();
            $table->timestamps();
        });

        Schema::create('why_choose_items', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('text')->nullable();
            $table->string('icon')->default('compass');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('why_choose_items');
        Schema::dropIfExists('site_settings');
    }
};
