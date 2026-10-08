# Indonesian ornament composition

The rebuilt SVGs preserve the pale line artwork from the client-supplied
“TinggalJalan — route detail visual concept” board (desktop 1440 / mobile 390).
They contain native vector paths, with no embedded raster image, fonts, UI,
network references, animation, or interaction. They are stylistic regional
interpretations, not an authenticated catalogue of traditional motifs.

## Assets

- orangutan-canopy.svg: complete face and hanging arm with supporting branch.
- foliage-sumatra-java.svg: continuous foliage, four-petal and floral motifs.
- regional-flow.svg: geometric woven motifs connected with leaves and flowers.
- regional-band.svg: low horizontal contour and small motifs.
- regional-mobile.svg: the reference's compact upper-right floral fragment.

Paths trace illustration ink in the supplied concept; they do not trace the
commercial UI. Pale source strokes were normalized into a CSS-colored mask.
The original homepage regional concept is no longer present in the workspace;
the homepage uses this recovered shared artwork family. This is not a claim
of an exact pixel match to the unavailable homepage board.

## Placement rules

Pure white canvas; secondary teal at .24 opacity (.16 for transactions).
Desktop begins at 1280px. All artwork retains its aspect ratio and bounded size.
It scrolls with its containing section; there is no fixed or sticky wallpaper.
The reference viewport is 1440px. Wider screens anchor art to the 1280px content
container, rather than sending it to distant browser edges.

| Element | Desktop position | Size |
| --- | --- | --- |
| Orangutan | 16px minimum from left; section top +16px on home, +96px on public/detail | 168px wide |
| Left foliage | Same left anchor; +304px home, +384px public/detail | 176px wide |
| Right flow | 16px minimum from right; section top +8px home, +96px public/detail | 288px wide |
| Section band | Section bottom +4px inset; 64px minimum side margins | 72px tall, contain |
| Gallery divider | Immediately after gallery in normal flow | 48px tall |

Desktop left-aligned intros have 112px additional clearance beside the orangutan.
Cards and photographic/tinted sections retain their opaque surfaces.

Home destination and featured-trip sections have separate canvases. Adding
rows extends the section and moves its bottom band; it never stretches artwork.
The About story section has its own composition beneath the opaque hero.
Booking and checkout use only the restrained mobile motif at every breakpoint.

Below 1280px the tall side art is replaced by the mobile composition. The motif
is 112px wide on phones, 160px on tablets. Bottom bands are 24px / 48px tall.
No decorative element receives pointer events or enters the accessibility tree.

## Review gate

Inspect actual 1440, 768 and 390px browser output, including section entrance,
exit, doubled destination content, variable route-title/gallery geometry, and
booking. Confirm no horizontal overflow or JavaScript errors. Screenshot review
precedes preview deployment; production requires separate user approval.
