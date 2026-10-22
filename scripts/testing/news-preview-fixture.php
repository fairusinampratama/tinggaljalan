<?php

// Temporary hosted review helper: writes only one new synthetic preview article.
ini_set('zend.exception_ignore_args', '1');
try {
    $root = '/home/u304629909/domains/preview.tinggaljalan.com';
    [$revision, $run, $mode] = array_slice($argv, 1);
    if (!preg_match('/^[a-f0-9]{40}$/', $revision) || !preg_match('/^\d+-\d+$/', $run)
        || !in_array($mode, ['prepare', 'inspect'], true)
        || trim(file_get_contents($root.'/.tinggaljalan-staging')) !== 'preview.tinggaljalan.com'
        || realpath($root.'/deployments/current') !== $root.'/deployments/releases/'.$revision) {
        throw new RuntimeException('Preview identity mismatch.');
    }
    $release = $root.'/deployments/releases/'.$revision;
    chdir($release);
    require $release.'/vendor/autoload.php';
    $app = require $release.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if (config('app.env') !== 'staging' || config('app.url') !== 'https://preview.tinggaljalan.com'
        || Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS name')->name !== 'u304629909_tj_preview') {
        throw new RuntimeException('Not the isolated preview database.');
    }
    foreach ([App\Models\EmailGatewaySetting::class, App\Models\WhatsappGatewaySetting::class,
        App\Models\NotificationSetting::class, App\Models\PaymentSetting::class] as $model) {
        if (!$model::query()->exists() || $model::where('is_enabled', true)->exists()) {
            throw new RuntimeException('Outbound integration safety failed.');
        }
    }
    $slug = 'preview-inline-photos-'.$run;
    if ($mode === 'prepare') {
        if (App\Models\NewsArticle::where('slug', $slug)->exists()) {
            throw new RuntimeException('Review fixture already exists; refusing to overwrite.');
        }
        $source = App\Models\NewsArticle::where('status', 'published')->firstOrFail();
        App\Models\NewsArticle::create([
            'slug' => $slug, 'article_category_id' => $source->article_category_id,
            'destination_id' => $source->destination_id,
            'title' => ['us' => 'Synthetic preview: section photo story '.$run],
            'excerpt' => ['us' => 'Synthetic editorial photo verification. Preview only.'],
            'cover_image' => $source->cover_image, 'cover_alt' => $source->cover_alt,
            'reading_time' => ['us' => '4 min read'], 'published_at' => now(),
            'status' => 'published', 'is_featured' => false,
            'sections' => [
                ['heading' => ['us' => 'Morning on the mountain'], 'body' => ['us' => 'Start early and leave time to watch the light change. These photographs support this section of the story.'], 'images' => []],
                ['heading' => ['us' => 'A slower journey home'], 'body' => ['us' => 'Take the quieter road through the hills. This is synthetic preview content for the editorial photo review.'], 'images' => []],
            ],
        ]);
    }
    $article = App\Models\NewsArticle::where('slug', $slug)->firstOrFail();
    $media = [];
    $directory = $root.'/deployments/shared/storage/app/public';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && !$file->isLink()) {
            $media[substr($file->getPathname(), strlen($directory))] = hash_file('sha256', $file->getPathname());
        }
    }
    ksort($media);
    $others = Illuminate\Support\Facades\DB::table('news_articles')->where('slug', '!=', $slug)->orderBy('id')->get();
    echo json_encode(['slug' => $slug, 'id' => $article->id, 'title' => $article->title['us'],
        'cover_image' => $article->cover_image, 'sections' => $article->sections,
        'other_news_sha256' => hash('sha256', json_encode($others, JSON_THROW_ON_ERROR)),
        'media' => $media], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
} catch (Throwable) {
    fwrite(STDERR, "Synthetic News preview check failed. No credentials logged.\n");
    exit(1);
}
