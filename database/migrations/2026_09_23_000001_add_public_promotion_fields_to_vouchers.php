<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->json('public_title')->nullable()->after('label');
            $table->json('public_description')->nullable()->after('public_title');
            $table->unsignedBigInteger('maximum_discount_idr')->nullable()->after('allowed_currencies');
            $table->unsignedInteger('maximum_discount_usd')->nullable()->after('maximum_discount_idr');
            $table->boolean('is_public')->default(false)->after('is_active');
            $table->unsignedInteger('sort_order')->default(0)->after('is_public');
            $table->index(['is_active', 'is_public', 'sort_order']);
        });

        Schema::create('tour_package_voucher', function (Blueprint $table): void {
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->primary(['tour_package_id', 'voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_package_voucher');

        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'is_public', 'sort_order']);
            $table->dropColumn([
                'public_title',
                'public_description',
                'maximum_discount_idr',
                'maximum_discount_usd',
                'is_public',
                'sort_order',
            ]);
        });
    }
};
