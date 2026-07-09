<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_packages', fn (Blueprint $table) => $table->string('pricing_mode')->default('flat')->after('difficulty'));

        Schema::create('package_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_pax');
            $table->unsignedInteger('max_pax');
            $table->unsignedBigInteger('price_idr');
            $table->unsignedInteger('price_usd');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tour_package_id', 'min_pax', 'max_pax']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('pricing_mode')->default('flat');
            $table->string('pricing_status')->default('priced');
            $table->foreignId('price_tier_id')->nullable()->constrained('package_price_tiers')->nullOnDelete();
            $table->unsignedInteger('tier_min_pax')->nullable();
            $table->unsignedInteger('tier_max_pax')->nullable();
            $table->unsignedBigInteger('unit_price')->nullable();
            $table->unsignedBigInteger('package_subtotal')->nullable();
            $table->timestamp('quoted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_tier_id');
            $table->dropColumn(['pricing_mode', 'pricing_status', 'tier_min_pax', 'tier_max_pax', 'unit_price', 'package_subtotal', 'quoted_at']);
        });
        Schema::dropIfExists('package_price_tiers');
        Schema::table('tour_packages', fn (Blueprint $table) => $table->dropColumn('pricing_mode'));
    }
};
