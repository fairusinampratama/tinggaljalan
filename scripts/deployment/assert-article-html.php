<?php

// Validate the initial article HTML against the same CMS payload React receives.
// This runs without Laravel or JavaScript on the hosting smoke-test path.
libxml_use_internal_errors(true);
$document = new DOMDocument;
$document->loadHTML('<?xml encoding="UTF-8">'.file_get_contents($argv[1]));
$xpath = new DOMXPath($document);
$payload = $xpath->query('//script[@data-page="app"]')->item(0);
if (! $payload) {
    // A future SSR deployment must validate its rendered article separately.
    fwrite(STDERR, "Cannot validate article: expected Inertia CMS payload.\n");
    exit(1);
}
$page = json_decode($payload->textContent, true, flags: JSON_THROW_ON_ERROR);
if (($page['component'] ?? '') !== 'NewsDetailPage') {
    exit(0);
}
$language = $page['props']['language'] ?? 'us';
$localized = function ($value, $region) {
    if (is_string($value)) {
        return $value;
    }
    foreach ([$region, 'us', 'en', 'id', 'cn'] as $key) {
        if (isset($value[$key]) && trim((string) $value[$key]) !== '') {
            return (string) $value[$key];
        }
    }

    return '';
};
$main = $xpath->query('//main[contains(@class,"server-seo-content")]')->item(0);
if (! $main) {
    fwrite(STDERR, "Article fallback missing.\n");
    exit(1);
}
$sections = $xpath->query('./section[@id]', $main);
$expected = $page['props']['article']['sections'] ?? [];
if ($sections->length !== count($expected)) {
    fwrite(STDERR, "Article section count mismatch.\n");
    exit(1);
}
foreach ($expected as $index => $section) {
    $actual = $sections->item($index);
    $heading = $xpath->query('./h2', $actual);
    if ($heading->length !== 1 || trim($heading->item(0)->textContent) !== trim($localized($section['heading'] ?? '', $language))) {
        fwrite(STDERR, "Article H2 text/order mismatch at section $index.\n");
        exit(1);
    }
    $body = trim(str_replace(["\r\n", "\r"], "\n", $localized($section['body'] ?? '', $language)));
    $paragraphs = array_values(array_filter(preg_split('/\n[\t ]*\n+/', $body), fn ($part) => $part !== ''));
    $actualParagraphs = $xpath->query('./p', $actual);
    if ($actualParagraphs->length !== count($paragraphs)) {
        fwrite(STDERR, "Article paragraph count mismatch.\n");
        exit(1);
    }
    foreach ($paragraphs as $i => $text) {
        if ($actualParagraphs->item($i)->textContent !== str_replace("\n", '', $text)
            || $xpath->query('./br', $actualParagraphs->item($i))->length !== substr_count($text, "\n")) {
            fwrite(STDERR, "Article paragraph text/line break mismatch.\n");
            exit(1);
        }
    }
}
