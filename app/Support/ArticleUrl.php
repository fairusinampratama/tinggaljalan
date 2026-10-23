<?php

namespace App\Support;

class ArticleUrl
{
    public static function path(string $slug): string
    {
        // Encode the raw database value once; never decode or rewrite legacy slugs.
        return '/news/'.rawurlencode($slug);
    }
}
