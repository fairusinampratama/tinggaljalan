<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price_idr')->nullable();
            $table->unsignedInteger('price_usd')->nullable();
            $table->string('pricing_type')->default('per_booking');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tour_package_id', 'add_on_id']);
        });

        if (Schema::hasTable('add_on_tour_package')) {
            DB::table('add_on_tour_package')
                ->join('add_ons', 'add_on_tour_package.add_on_id', '=', 'add_ons.id')
                ->select([
                    'add_on_tour_package.tour_package_id',
                    'add_on_tour_package.add_on_id',
                    'add_ons.price_idr',
                    'add_ons.price_usd',
                    'add_ons.pricing_type',
                ])
                ->orderBy('add_on_tour_package.tour_package_id')
                ->orderBy('add_on_tour_package.add_on_id')
                ->chunk(500, function ($rows): void {
                    $now = now();

                    $payload = $rows->map(fn ($row): array => [
                        'tour_package_id' => $row->tour_package_id,
                        'add_on_id' => $row->add_on_id,
                        'price_idr' => $row->price_idr,
                        'price_usd' => $row->price_usd,
                        'pricing_type' => $row->pricing_type ?? 'per_booking',
                        'sort_order' => 0,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    DB::table('package_add_ons')->insertOrIgnore($payload);
                });

            Schema::dropIfExists('add_on_tour_package');
        }
    }

    public function down(): void
    {
        Schema::create('add_on_tour_package', function (Blueprint $table) {
            $table->foreignId('add_on_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->primary(['add_on_id', 'tour_package_id']);
        });

        DB::table('package_add_ons')
            ->select(['add_on_id', 'tour_package_id'])
            ->orderBy('tour_package_id')
            ->orderBy('add_on_id')
            ->chunk(500, function ($rows): void {
                DB::table('add_on_tour_package')->insertOrIgnore(
                    $rows->map(fn ($row): array => [
                        'add_on_id' => $row->add_on_id,
                        'tour_package_id' => $row->tour_package_id,
                    ])->all(),
                );
            });

        Schema::dropIfExists('package_add_ons');
    }
};
