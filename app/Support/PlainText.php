<?php

namespace App\Support;

class PlainText
{
    public static function paragraphs(string $text): array
    {
        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $text));

        return array_values(array_filter(preg_split('/\n[\t ]*\n+/', $normalized), fn ($part) => $part !== ''));
    }
}
