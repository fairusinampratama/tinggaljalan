# News inline editorial photographs

Each optional `sections[].images` array stores ordered objects with `path`, localized
`alt` and optional localized `caption` (`us`, `id`, `cn`). Existing section heading,
body, legacy section formats, and independent article covers remain supported.
No schema migration or new media table is required.

In Article content, add a photograph inside its section, enter an English alt
description, optionally add captions/translations, and use the reorder controls.
JPEG, PNG and WebP use the existing 4 MB single-image helper, public disk and
responsive WebP generation. Reopening a record hydrates the same upload references.
An empty photo row is allowed; alt validation applies when its upload is present.
Removing/replacing a reference or deleting an article does not delete media files.
Physical cleanup is deliberately outside this feature.

Public detail serialization normalizes local image paths, removes malformed or
empty entries, strips metadata markup and uses the existing localization fallback.
Image dimensions are read from existing originals when available. Listing and
related-article cards exclude section content. Initial Blade HTML and React show
ordered figures after each section body, with responsive sources, lazy loading,
alt descriptions and optional captions. The cover remains the Open Graph and
Article JSON-LD image. Existing navigation anchors and text rendering are unchanged.

Focused verification: `php artisan test --filter=NewsInlinePhotosTest` and
`npx playwright test news-inline-photos`. The browser fixture refuses every database
except the disposable testing SQLite database. It creates a synthetic story with
landscape and portrait photographs; seeded legacy stories remain unchanged.

Release boundary: review implementation and screenshots first; separately approve
protected preview deployment, inspect its actual revision, then separately approve
merge/production. No automatic publication is part of this feature.
