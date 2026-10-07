<?php

// Only media-bearing columns reviewed against the actual admin forms/schema.
function refreshMediaFields(): array
{
    return [
        'destinations' => ['cover_image' => null],
        'tour_packages' => ['cover_image' => null, 'gallery' => '*'],
        'news_articles' => ['cover_image' => null],
        'hero_slides' => ['desktop_image' => null, 'mobile_image' => null],
        'team_members' => ['portrait' => null],
        'company_milestones' => ['image' => null],
        'platform_links' => ['logo' => null],
        'site_settings' => ['logo_url' => null],
        'about_pages' => ['hero' => 'image', 'story' => 'image', 'seo' => 'image'],
    ];
}

function refreshVisitMedia(PDO $db, callable $visit): void
{
    foreach (refreshMediaFields() as $table => $fields) {
        foreach ($db->query('SELECT id,'.implode(',', array_keys($fields)).' FROM '.refreshIdentifier($table))->fetchAll(PDO::FETCH_ASSOC) as $row) {
            foreach ($fields as $column => $nested) {
                $original = $row[$column];
                if ($nested === null) {
                    $updated = $visit($original);
                } elseif ($original === null || $original === '') {
                    continue;
                } else {
                    $value = json_decode($original, true, flags: JSON_THROW_ON_ERROR);
                    refreshAssert(is_array($value));
                    if ($nested === '*') {
                        $updated = array_map($visit, $value);
                    } else {
                        $updated = $value;
                        if (array_key_exists($nested, $value)) {
                            $updated[$nested] = $visit($value[$nested]);
                        }
                    }
                    if ($updated === $value) {
                        continue;
                    }
                    $updated = json_encode($updated, JSON_THROW_ON_ERROR);
                }
                if ($updated !== $original) {
                    $db->prepare('UPDATE '.refreshIdentifier($table).' SET '.refreshIdentifier($column).' = ? WHERE id = ?')->execute([$updated, $row['id']]);
                }
            }
        }
    }
}

function refreshMediaPath(?string $value): ?array
{
    if ($value === null || $value === '') {
        return null;
    }
    refreshAssert(! preg_match('/[\x00-\x20]/', $value));
    if (preg_match('~^https?://~i', $value)) {
        $url = parse_url($value);
        refreshAssert(is_array($url) && ! isset($url['user']) && ! isset($url['pass']));
        if (! in_array(strtolower($url['host'] ?? ''), ['tinggaljalan.com', 'www.tinggaljalan.com', 'preview.tinggaljalan.com'], true)) {
            return ['external', $value];
        }
        refreshAssert(! isset($url['port']) || in_array($url['port'], [80, 443], true));
        $value = $url['path'] ?? '';
    }
    $path = preg_replace('~^/?(?:public/)?~', '', $value);
    refreshAssert(! str_contains($path, '..') && ! str_contains($path, '//') && preg_match('~^[a-zA-Z0-9_./-]+\.(?:jpg|jpeg|png|webp|svg)$~i', $path) === 1);
    if (str_starts_with($path, 'images/')) {
        return ['static', $path];
    }
    $path = preg_replace('~^storage/~', '', $path);
    $allowed = ['admin/hero/', 'admin/packages/covers/', 'admin/packages/gallery/', 'admin/destinations/covers/', 'admin/news/covers/', 'admin/about/', 'admin/site/', 'admin/platform-links/logos/', 'admin/preview-refresh/'];
    refreshAssert(count(array_filter($allowed, fn ($directory) => str_starts_with($path, $directory))) > 0);

    return ['upload', $path];
}

function refreshValidImage(string $file): bool
{
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'svg') {
        return getimagesize($file) !== false;
    }
    // Uploaded platform logos allow SVG. Reject active content/external entities.
    $xml = file_get_contents($file);
    if ($xml === false || strlen($xml) > 1048576 || preg_match('/<!DOCTYPE|<!ENTITY|<\s*(?:script|foreignObject)|\bon[a-z]+\s*=|(?:href|src)\s*=|url\s*\(/i', $xml)) {
        return false;
    }
    $dom = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    try {
        if (! $dom->loadXML($xml, LIBXML_NONET) || $dom->documentElement?->localName !== 'svg') {
            return false;
        }
        $passive = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'defs', 'linearGradient', 'radialGradient', 'stop', 'clipPath', 'mask', 'pattern', 'text', 'tspan', 'title', 'desc'];
        foreach ($dom->getElementsByTagName('*') as $element) {
            if (! in_array($element->localName, $passive, true) || $element->namespaceURI !== 'http://www.w3.org/2000/svg') {
                return false;
            }
        }

        return true;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

function refreshCanonicalFile(string $root, string $relative): string
{
    $path = $root.'/'.$relative;
    refreshAssert(realpath($root) === $root && realpath($path) === $path && is_file($path) && ! is_link($path));

    return $path;
}

function refreshMediaRoots(string $productionRoot, string $previewShared): array
{
    $source = $productionRoot.'/deployments/shared/storage/app/public';
    $target = $previewShared.'/storage/app/public';
    refreshAssert(realpath($source) === $source && realpath($target) === $target);
    refreshAssert($source !== $target && ! str_starts_with($target, $source.'/') && ! str_starts_with($source, $target.'/'));

    return [$source, $target];
}

function refreshEnsureMediaDirectory(string $root, string $relative): string
{
    $path = $root;
    foreach (explode('/', $relative) as $part) {
        refreshAssert(preg_match('/^[a-z0-9-]+$/', $part) === 1);
        $path .= '/'.$part;
        refreshAssert(! is_link($path));
        if (! is_dir($path)) {
            refreshAssert(mkdir($path, 0755));
        }
        refreshAssert(realpath($path) === $path && chmod($path, 0755));
    }

    return $path;
}

function refreshManagedMediaState(string $previewShared): array
{
    $root = $previewShared.'/storage/app/public';
    refreshAssert(realpath($root) === $root);
    $state = [];
    foreach (['admin/preview-refresh', 'generated/storage/admin/preview-refresh'] as $relative) {
        $directory = $root.'/'.$relative;
        refreshAssert(! is_link($directory));
        if (! file_exists($directory)) {
            continue;
        }
        refreshAssert(realpath($directory) === $directory && is_dir($directory));
        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isDot()) {
                continue;
            }
            refreshAssert($file->isFile() && ! $file->isLink());
            $name = $file->getFilename();
            refreshAssert(preg_match('/^(?:[a-f0-9]{64}(?:-(?:480|768|960|1200|1600))?\.(?:jpg|jpeg|png|webp|svg)|\.refresh-[a-f0-9]+\.tmp)$/', $name) === 1);
            $state[$relative.'/'.$name] = hash_file('sha256', $file->getPathname());
        }
    }
    ksort($state);

    return $state;
}

function refreshRestoreMedia(string $previewShared, array $baseline): void
{
    $root = $previewShared.'/storage/app/public';
    $current = refreshManagedMediaState($previewShared);
    foreach ($baseline as $relative => $digest) {
        refreshAssert(isset($current[$relative]) && hash_equals($digest, $current[$relative]));
    }
    foreach (array_diff_key($current, $baseline) as $relative => $digest) {
        refreshAssert(unlink(refreshCanonicalFile($root, $relative)));
    }
    refreshAssert(refreshManagedMediaState($previewShared) === $baseline);
}

function refreshCopyPublicImages(PDO $db, string $productionRoot, string $previewShared): int
{
    [$sourceRoot, $public] = refreshMediaRoots($productionRoot, $previewShared);
    $targetRoot = refreshEnsureMediaDirectory($public, 'admin/preview-refresh');
    $copied = [];
    refreshVisitMedia($db, function ($value) use (&$copied, $sourceRoot, $targetRoot) {
        refreshAssert($value === null || is_string($value));
        $media = refreshMediaPath($value);
        if ($media === null || $media[0] === 'external') {
            return $value;
        }
        if ($media[0] === 'static') {
            return '/'.$media[1];
        }
        $GLOBALS['refreshDetail'] = 'allowlisted-source-image';
        // A production DB must not point into the preview-owned namespace.
        refreshAssert(! str_starts_with($media[1], 'admin/preview-refresh/'));
        $source = refreshCanonicalFile($sourceRoot, $media[1]);
        refreshAssert(refreshValidImage($source));
        $digest = hash_file('sha256', $source);
        $name = $digest.'.'.strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $destination = $targetRoot.'/'.$name;
        refreshAssert(! is_link($destination));
        if (! file_exists($destination)) {
            $temporary = $targetRoot.'/.refresh-'.bin2hex(random_bytes(12)).'.tmp';
            try {
                refreshAssert(copy($source, $temporary));
                refreshAssert(hash_file('sha256', $temporary) === $digest && hash_file('sha256', $source) === $digest);
                refreshAssert(chmod($temporary, 0644) && rename($temporary, $destination));
            } finally {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        }
        refreshAssert(hash_file('sha256', $destination) === $digest && hash_file('sha256', $source) === $digest);
        $copied[$name] = true;

        return '/storage/admin/preview-refresh/'.$name;
    });
    unset($GLOBALS['refreshDetail']);

    return count($copied);
}

function refreshVerifyMedia(PDO $db, string $previewShared, string $release, bool $derived = false): array
{
    $public = $previewShared.'/storage/app/public';
    $static = $release.'/public';
    refreshAssert(realpath($public) === $public && realpath($static) === $static);
    $uploads = [];
    refreshVisitMedia($db, function ($value) use ($public, $static, $derived, &$uploads) {
        refreshAssert($value === null || is_string($value));
        $media = refreshMediaPath($value);
        if ($media === null || $media[0] === 'external') {
            return $value;
        }
        $file = refreshCanonicalFile($media[0] === 'static' ? $static : $public, $media[1]);
        refreshAssert($media[0] === 'static' && str_ends_with($media[1], '.svg') || refreshValidImage($file));
        if ($media[0] === 'upload') {
            refreshAssert(preg_match('~^admin/preview-refresh/([a-f0-9]{64})\.(?:jpg|jpeg|png|webp|svg)$~', $media[1], $matches) === 1);
            refreshAssert(hash_file('sha256', $file) === $matches[1]);
            $uploads[$media[1]] = true;
            if ($derived && ! str_ends_with($media[1], '.svg')) {
                foreach ([480, 768, 960, 1200, 1600] as $width) {
                    $relative = 'generated/storage/'.preg_replace('/\.[a-z0-9]+$/', '', $media[1]).'-'.$width.'.webp';
                    $variant = refreshCanonicalFile($public, $relative);
                    $info = getimagesize($variant);
                    refreshAssert($info !== false && $info['mime'] === 'image/webp' && $info[0] === min($width, getimagesize($file)[0]));
                }
            }
        }

        return $value;
    });

    return array_keys($uploads);
}

function refreshGenerateMedia(PDO $db, string $previewShared, string $release, object $generator): int
{
    refreshAssert($generator->isSupported());
    $paths = refreshVerifyMedia($db, $previewShared, $release);
    refreshEnsureMediaDirectory($previewShared.'/storage/app/public', 'generated/storage/admin/preview-refresh');
    $mask = umask(0022);
    $count = 0;
    try {
        foreach ($paths as $path) {
            if (! str_ends_with($path, '.svg')) {
                $count += $generator->generateForPublicDiskPath($path, true);
            }
        }
    } finally {
        umask($mask);
    }
    refreshVerifyMedia($db, $previewShared, $release, true);

    return $count;
}

function refreshPreviewLink(?string $value): ?string
{
    if ($value === null || $value === '') {
        return $value;
    }
    if ($value === 'whatsapp' || preg_match('~^(?:mailto:|tel:|https?://(?:wa\.me|(?:api|web)\.whatsapp\.com)(?:/|$))~i', $value)) {
        return null;
    }
    $url = parse_url($value);
    refreshAssert(is_array($url) && ! isset($url['user']) && ! isset($url['pass']));
    if (isset($url['scheme']) && ! in_array(strtolower($url['scheme']), ['http', 'https'], true)) {
        return null;
    }
    if (! isset($url['host']) || in_array(strtolower($url['host']), ['tinggaljalan.com', 'www.tinggaljalan.com'], true)) {
        $path = $url['path'] ?? '/';
        if (str_starts_with($path, '//') || preg_match('/(?:token|secret|signature|api_key)=/i', $url['query'] ?? '')) {
            return null;
        }
        // Never retain production payment tokens/callback links in content links.
        if (preg_match('~^/(?:checkout|api|admin|payment|webhook|callback)(?:/|$)~i', $path)) {
            return null;
        }

        return $path.(isset($url['query']) ? '?'.$url['query'] : '').(isset($url['fragment']) ? '#'.$url['fragment'] : '');
    }

    return $value;
}

function refreshSanitizeLinks(PDO $db): void
{
    foreach (['hero_slides' => ['primary_cta_url', 'secondary_cta_url'], 'platform_links' => ['url'], 'team_members' => ['profile_url']] as $table => $columns) {
        foreach ($db->query('SELECT id,'.implode(',', $columns).' FROM '.refreshIdentifier($table))->fetchAll(PDO::FETCH_ASSOC) as $row) {
            foreach ($columns as $column) {
                $value = refreshPreviewLink($row[$column]);
                if ($table === 'platform_links' && $value === null) {
                    $value = '/routes';
                    $db->prepare('UPDATE platform_links SET is_active = 0 WHERE id = ?')->execute([$row['id']]);
                }
                $db->prepare('UPDATE '.refreshIdentifier($table).' SET '.refreshIdentifier($column).' = ? WHERE id = ?')->execute([$value, $row['id']]);
            }
        }
    }
    foreach ($db->query('SELECT id,cta FROM about_pages')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['cta'] === null) {
            continue;
        }
        $cta = json_decode($row['cta'], true, flags: JSON_THROW_ON_ERROR);
        foreach (['primary_url', 'secondary_url'] as $key) {
            if (isset($cta[$key])) {
                $cta[$key] = refreshPreviewLink($cta[$key]);
            }
        }
        $db->prepare('UPDATE about_pages SET cta = ? WHERE id = ?')->execute([json_encode($cta, JSON_THROW_ON_ERROR), $row['id']]);
    }
}

function refreshPruneBackups(string $directory, string $current): void
{
    refreshAssert(basename($directory) === 'database-backups' && realpath($directory) === $directory);
    $files = [];
    foreach (new DirectoryIterator($directory) as $file) {
        if ($file->isDot()) {
            continue;
        }
        refreshAssert($file->isFile() && ! $file->isLink() && preg_match('/^preview-before-[0-9]+-[0-9]+\.jsonl\.gz$/', $file->getFilename()) === 1);
        $files[$file->getFilename()] = $file->getMTime();
    }
    arsort($files);
    refreshAssert(isset($files[$current]));
    $keep = array_merge([$current], array_slice(array_keys(array_diff_key($files, [$current => true])), 0, 2));
    foreach (array_keys($files) as $file) {
        if (! in_array($file, $keep, true)) {
            refreshAssert(unlink(refreshCanonicalFile($directory, $file)));
        }
    }
}
