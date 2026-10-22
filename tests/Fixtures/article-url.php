<?php

use App\Models\NewsArticle;
use Illuminate\Contracts\Console\Kernel;

// Browser fixture only: never run against a hosted database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(config('database.connections.sqlite.database')) !== realpath(storage_path('framework/testing/browser.sqlite'))) {
    throw new RuntimeException('Article URL fixtures require the disposable browser test database.');
}

$article = NewsArticle::where('slug', 'paket-wisata-bromo-dari-malang')->firstOrFail()->replicate();
$article->slug = 'Legacy Bromo 100%20 & Friends';
$article->title = ['us' => 'Legacy URL compatibility story'];
$article->is_featured = false;
$article->published_at = now();
$article->save();
