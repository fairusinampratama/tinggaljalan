<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('source_refs')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('seo')->nullable();
            $table->timestamps();
        });

        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('category')->nullable();
            $table->json('tag')->nullable();
            $table->json('excerpt')->nullable();
            $table->json('intro')->nullable();
            $table->json('best_for')->nullable();
            $table->string('duration')->nullable();
            $table->json('difficulty')->nullable();
            $table->unsignedBigInteger('base_price_idr')->nullable();
            $table->unsignedInteger('base_price_usd')->nullable();
            $table->text('price_note')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('cover_alt')->nullable();
            $table->json('gallery')->nullable();
            $table->json('pickup_areas')->nullable();
            $table->json('pickup_label')->nullable();
            $table->json('group_type')->nullable();
            $table->json('highlights')->nullable();
            $table->json('includes')->nullable();
            $table->json('excludes')->nullable();
            $table->json('notes')->nullable();
            $table->json('details')->nullable();
            $table->json('good_to_know')->nullable();
            $table->json('policies')->nullable();
            $table->json('testimonials')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->json('review_source')->nullable();
            $table->json('styles')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('itinerary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('day_number')->default(1);
            $table->string('time_label')->nullable();
            $table->json('title');
            $table->json('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('package_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->string('source_key')->nullable();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->unsignedBigInteger('price_idr')->nullable();
            $table->unsignedInteger('price_usd')->nullable();
            $table->string('pricing_type')->default('per_booking');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tour_package_id', 'source_key']);
        });

        Schema::create('package_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('status')->default('available');
            $table->unsignedInteger('seats_left')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('article_category_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('excerpt');
            $table->string('cover_image')->nullable();
            $table->json('cover_alt')->nullable();
            $table->json('tags')->nullable();
            $table->json('sections')->nullable();
            $table->json('reading_time')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('content_updated_at')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->json('seo')->nullable();
            $table->timestamps();
        });

        Schema::create('news_article_tour_package', function (Blueprint $table) {
            $table->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->primary(['news_article_id', 'tour_package_id']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2);
            $table->string('currency')->nullable();
            $table->json('allowed_currencies')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('tour_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('whatsapp_country_code')->nullable();
            $table->string('communication_language', 2)->default('en');
            $table->date('travel_date')->nullable();
            $table->unsignedInteger('pax')->default(1);
            $table->string('pickup')->nullable();
            $table->string('traveler_type')->default('local');
            $table->string('currency', 3)->default('IDR');
            $table->json('selected_add_ons')->nullable();
            $table->string('voucher_code')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('payment_gateway')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('new');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->json('question');
            $table->json('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('origin')->nullable();
            $table->decimal('rating', 3, 2)->default(5);
            $table->unsignedInteger('review_count')->nullable();
            $table->json('source')->nullable();
            $table->json('text');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('trust_stats', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('value');
            $table->string('icon_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_links', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('logo')->nullable();
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_links');
        Schema::dropIfExists('trust_stats');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('news_article_tour_package');
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('article_categories');
        Schema::dropIfExists('package_availabilities');
        Schema::dropIfExists('package_add_ons');
        Schema::dropIfExists('itinerary_items');
        Schema::dropIfExists('tour_packages');
        Schema::dropIfExists('destinations');
    }
};
