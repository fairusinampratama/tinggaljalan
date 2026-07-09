<?php

namespace Database\Seeders;

use App\Models\NewsArticle;
use App\Models\TourPackage;
use Illuminate\Database\Seeder;

class DummyNewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating 50 dummy news articles...');

        $packages = TourPackage::query()->pluck('id');

        NewsArticle::factory(50)->create()->each(function (NewsArticle $article) use ($packages) {
            // Attach 0 to 3 random packages to each article
            if ($packages->isNotEmpty()) {
                $attachCount = fake()->numberBetween(0, 3);
                if ($attachCount > 0) {
                    $randomPackageIds = $packages->random($attachCount);
                    $article->tourPackages()->sync($randomPackageIds);
                }
            }
        });

        $this->command->info('Successfully created 50 dummy news articles!');
    }
}
