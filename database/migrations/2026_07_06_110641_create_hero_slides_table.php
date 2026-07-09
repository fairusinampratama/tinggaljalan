<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('desktop_image');
            $table->string('mobile_image')->nullable();
            $table->json('image_alt')->nullable();
            $table->json('eyebrow')->nullable();
            $table->json('heading')->nullable();
            $table->json('description')->nullable();
            $table->json('primary_cta_label')->nullable();
            $table->string('primary_cta_url')->nullable();
            $table->json('secondary_cta_label')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->string('text_alignment')->default('left');
            $table->string('focal_position')->default('center');
            $table->integer('overlay_strength')->default(40);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
