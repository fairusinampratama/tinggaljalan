<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_add_ons', function (Blueprint $table) {
            if (! Schema::hasColumn('package_add_ons', 'source_key')) {
                $table->string('source_key')->nullable()->after('tour_package_id');
            }

            if (! Schema::hasColumn('package_add_ons', 'title')) {
                $table->json('title')->nullable()->after('source_key');
            }

            if (! Schema::hasColumn('package_add_ons', 'description')) {
                $table->json('description')->nullable()->after('title');
            }
        });

        if (Schema::hasTable('add_ons') && Schema::hasColumn('package_add_ons', 'add_on_id')) {
            DB::table('package_add_ons')
                ->join('add_ons', 'package_add_ons.add_on_id', '=', 'add_ons.id')
                ->select([
                    'package_add_ons.id',
                    'add_ons.slug',
                    'add_ons.title',
                    'add_ons.description',
                    'add_ons.price_idr',
                    'add_ons.price_usd',
                    'add_ons.pricing_type',
                ])
                ->orderBy('package_add_ons.id')
                ->chunk(500, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('package_add_ons')
                            ->where('id', $row->id)
                            ->update([
                                'source_key' => $row->slug,
                                'title' => $row->title,
                                'description' => $row->description,
                                'price_idr' => $row->price_idr,
                                'price_usd' => $row->price_usd,
                                'pricing_type' => $row->pricing_type ?? 'per_booking',
                            ]);
                    }
                });
        }

        if (Schema::hasColumn('package_add_ons', 'add_on_id')) {
            try {
                Schema::table('package_add_ons', function (Blueprint $table) {
                    $table->dropForeign(['add_on_id']);
                });
            } catch (Throwable) {
                //
            }

            try {
                Schema::table('package_add_ons', function (Blueprint $table) {
                    $table->index('tour_package_id');
                });
            } catch (Throwable) {
                // The index may exist after a failed retry.
            }

            try {
                DB::statement('ALTER TABLE package_add_ons DROP INDEX package_add_ons_tour_package_id_add_on_id_unique');
            } catch (Throwable) {
                // The failed migration may have already removed this index.
            }

            Schema::table('package_add_ons', function (Blueprint $table) {
                $table->dropColumn('add_on_id');
            });
        }

        try {
            Schema::table('package_add_ons', function (Blueprint $table) {
                $table->unique(['tour_package_id', 'source_key']);
            });
        } catch (Throwable) {
            //
        }

        Schema::dropIfExists('add_ons');
    }

    public function down(): void
    {
        Schema::create('add_ons', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description')->nullable();
            $table->unsignedBigInteger('price_idr')->nullable();
            $table->unsignedInteger('price_usd')->nullable();
            $table->string('pricing_type')->default('per_booking');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('package_add_ons')
            ->select(['source_key', 'title', 'description', 'price_idr', 'price_usd', 'pricing_type'])
            ->whereNotNull('source_key')
            ->orderBy('source_key')
            ->chunk(500, function ($rows): void {
                foreach ($rows->unique('source_key') as $row) {
                    DB::table('add_ons')->insertOrIgnore([
                        'slug' => $row->source_key,
                        'title' => $row->title ?? json_encode(['us' => $row->source_key]),
                        'description' => $row->description,
                        'price_idr' => $row->price_idr,
                        'price_usd' => $row->price_usd,
                        'pricing_type' => $row->pricing_type ?? 'per_booking',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        Schema::table('package_add_ons', function (Blueprint $table) {
            $table->dropUnique('package_add_ons_tour_package_id_source_key_unique');
            $table->foreignId('add_on_id')->nullable()->after('tour_package_id')->constrained()->nullOnDelete();
        });

        if (Schema::hasTable('add_ons')) {
            DB::table('package_add_ons')
                ->join('add_ons', 'package_add_ons.source_key', '=', 'add_ons.slug')
                ->select(['package_add_ons.id', 'add_ons.id as add_on_id'])
                ->orderBy('package_add_ons.id')
                ->chunk(500, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('package_add_ons')
                            ->where('id', $row->id)
                            ->update(['add_on_id' => $row->add_on_id]);
                    }
                });
        }

        Schema::table('package_add_ons', function (Blueprint $table) {
            $table->dropColumn(['source_key', 'title', 'description']);
            $table->unique(['tour_package_id', 'add_on_id']);
        });
    }
};
