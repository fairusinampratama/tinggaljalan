<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tour_packages', 'seo')) {
            return;
        }

        Schema::table('tour_packages', function (Blueprint $table): void {
            $table->dropColumn('seo');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tour_packages', 'seo')) {
            return;
        }

        Schema::table('tour_packages', function (Blueprint $table): void {
            $table->json('seo')->nullable()->after('is_active');
        });
    }
};
