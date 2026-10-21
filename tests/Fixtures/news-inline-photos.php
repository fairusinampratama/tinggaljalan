<?php

use App\Models\NewsArticle;
use App\Support\ResponsiveImageGenerator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

// Browser-test fixture only. Never run against preview or production.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(config('database.connections.sqlite.database')) !== realpath(storage_path('framework/testing/browser.sqlite'))) {
    throw new RuntimeException('Inline-photo fixtures require the disposable browser test database.');
}

$photos = [
    ['source' => 'images/hero-bromo.jpg', 'name' => 'testing-mountain.jpg', 'alt' => 'Morning light over Mount Bromo', 'caption' => 'The landscape opens up as the morning light reaches the caldera.'],
    ['source' => 'images/routes/unique/tumpak-sewu-daytrip.jpg', 'name' => 'testing-waterfall.jpg', 'alt' => 'Waterfalls falling into the green valley', 'caption' => 'A quieter stop on the walk down to the valley.'],
    ['source' => 'images/gallery-indonesia-green.jpg', 'name' => 'testing-forest.jpg', 'alt' => 'Green hills along the journey', 'caption' => ''],
];
$images = [];
foreach ($photos as $photo) {
    $path = 'admin/news/sections/'.$photo['name'];
    Storage::disk('public')->put($path, file_get_contents(public_path($photo['source'])));
    app(ResponsiveImageGenerator::class)->generateForPublicDiskPath($path);
    $images[] = ['path' => $path, 'alt' => ['us' => $photo['alt'], 'id' => '', 'cn' => ''],
        'caption' => ['us' => $photo['caption'], 'id' => '', 'cn' => '']];
}
$source = NewsArticle::where('slug', 'paket-wisata-bromo-dari-malang')->firstOrFail();
NewsArticle::updateOrCreate(['slug' => 'inline-editorial-photo-story'], [
    'article_category_id' => $source->article_category_id, 'destination_id' => $source->destination_id,
    'title' => ['us' => 'From mountain light to a green valley'],
    'excerpt' => ['us' => 'A local journey told through short notes and photographs from each stop.'],
    'cover_image' => $source->cover_image, 'cover_alt' => $source->cover_alt,
    'reading_time' => ['us' => '4 min read'], 'published_at' => '2026-10-01 08:00:00',
    'status' => 'published', 'is_featured' => false,
    'sections' => [
        ['heading' => ['us' => 'Morning on the mountain'], 'body' => ['us' => "Start early and leave time to watch the light change. The walk is part of the experience, with plenty of room to stop and look around.\n\nThese photographs belong to this part of the story. They appear in the order chosen by the editor."], 'images' => [$images[0], $images[1]]],
        ['heading' => ['us' => 'A slower journey home'], 'body' => ['us' => 'After the early start, take the quieter road through the hills. A short stop with the guide gives the day a gentler finish.'], 'images' => [$images[2]]],
    ],
]);
echo "Disposable inline-photo article ready.\n";
