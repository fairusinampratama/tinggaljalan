<?php

namespace Database\Factories;

use App\Models\ArticleCategory;
use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsArticle>
 */
class NewsArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleUs = fake()->catchPhrase();
        $titleId = 'ID: ' . $titleUs;
        $titleCn = 'CN: ' . $titleUs;
        $slug = Str::slug($titleUs) . '-' . fake()->numberBetween(1000, 9999);

        return [
            'slug' => $slug,
            'destination_id' => Destination::query()->inRandomOrder()->first()?->id,
            'article_category_id' => ArticleCategory::query()->inRandomOrder()->first()?->id,
            'title' => [
                'us' => $titleUs,
                'id' => $titleId,
                'cn' => $titleCn,
            ],
            'excerpt' => [
                'us' => fake()->sentence(10),
                'id' => fake('id_ID')->sentence(10),
                'cn' => fake()->sentence(10) . ' cn',
            ],
            'cover_image' => fake()->randomElement([
                '/images/hero-bromo.jpg',
                '/images/routes/bromo-jeep.jpg',
                '/images/destinations/bromo.jpg',
                '/images/gallery-indonesia-green.jpg',
                '/images/routes/tumpak-sewu.jpg',
                '/images/routes/jogja.jpg',
                '/images/routes/medan.jpg',
            ]),
            'cover_alt' => [
                'us' => 'Travel cover image',
                'id' => 'Gambar sampul',
                'cn' => '封面图片',
            ],
            'tags' => fake()->randomElements([
                ['us' => 'Tips', 'id' => 'Tips', 'cn' => 'Tips'],
                ['us' => 'Itinerary', 'id' => 'Itinerary', 'cn' => 'Itinerary'],
                ['us' => 'Culinary', 'id' => 'Kuliner', 'cn' => 'Culinary'],
                ['us' => 'Culture', 'id' => 'Budaya', 'cn' => 'Culture'],
                ['us' => 'Budget', 'id' => 'Budget', 'cn' => 'Budget'],
            ], fake()->numberBetween(1, 3)),
            'sections' => [
                [
                    'heading' => [
                        'us' => 'Planning note',
                        'id' => 'Catatan perjalanan',
                        'cn' => 'Planning note',
                    ],
                    'body' => [
                        'us' => fake()->paragraph(3),
                        'id' => fake('id_ID')->paragraph(3),
                        'cn' => fake()->paragraph(3) . ' cn',
                    ],
                ],
            ],
            'reading_time' => [
                'us' => fake()->numberBetween(2, 10) . ' min read',
                'id' => fake()->numberBetween(2, 10) . ' mnt baca',
                'cn' => fake()->numberBetween(2, 10) . ' 分钟阅读',
            ],
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => 'published',
            'is_featured' => false,
        ];
    }
}
