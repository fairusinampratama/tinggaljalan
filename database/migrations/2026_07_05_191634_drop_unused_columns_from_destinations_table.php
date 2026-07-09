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
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn(['description', 'gallery', 'source_refs', 'seo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->json('description')->nullable();
            $table->json('gallery')->nullable();
            $table->json('source_refs')->nullable();
            $table->json('seo')->nullable();
        });
    }
};
