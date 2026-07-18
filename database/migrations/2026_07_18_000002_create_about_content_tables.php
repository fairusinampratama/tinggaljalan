<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_pages', function (Blueprint $table) {
            $table->id();
            $table->string('seed_key')->nullable()->unique();
            $table->boolean('is_published')->default(false);
            $table->json('section_visibility')->nullable();
            $table->json('hero')->nullable();
            $table->json('story')->nullable();
            $table->json('values_section')->nullable();
            $table->json('team_section')->nullable();
            $table->json('milestones_section')->nullable();
            $table->json('workflow_section')->nullable();
            $table->json('profile_section')->nullable();
            $table->json('cta')->nullable();
            $table->json('seo')->nullable();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('seed_key')->nullable()->unique();
            $table->string('name');
            $table->json('role')->nullable();
            $table->json('biography')->nullable();
            $table->string('portrait')->nullable();
            $table->json('portrait_alt')->nullable();
            $table->string('category')->default('team');
            $table->string('location')->nullable();
            $table->json('languages')->nullable();
            $table->string('profile_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_sample')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('company_milestones', function (Blueprint $table) {
            $table->id();
            $table->string('seed_key')->nullable()->unique();
            $table->json('period')->nullable();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->json('image_alt')->nullable();
            $table->boolean('is_sample')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_milestones');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('about_pages');
    }
};
