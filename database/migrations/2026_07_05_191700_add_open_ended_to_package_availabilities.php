<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_availabilities', function (Blueprint $table): void {
            $table->boolean('is_open_ended')->default(false)->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('package_availabilities', function (Blueprint $table): void {
            $table->dropColumn('is_open_ended');
        });
    }
};
