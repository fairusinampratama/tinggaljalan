# Product itinerary and gallery

Route detail adds `itinerarySteps`: stable record ID, dayNumber, timeLabel,
localized title/description and displayOrder. The existing `itinerary` text
payload remains supported. The relation's day/sort ordering is authoritative;
clock labels are never parsed or sorted. Multiple saved days receive separators.

The timeline occupies the existing main column above the other detail lists.
Its complete text also appears in the initial server document. Booking, prices,
cover images and route metadata are unchanged.

Gallery remains optional, with 2–20 images when populated. Existing storage,
ordering and responsive generation are reused. Thumbnail and main pictures use
responsive WebP sources with original-image fallback; adjacent preload uses a
960px derivative. Full originals are requested for the open lightbox. Thumbnail
strips scroll themselves, including when a lightbox opens, without scrolling the
page. Single-image layouts use the entire main column.

Verification: ProductItineraryGalleryTest, FilamentAdminResourcesTest,
PublicHtmlParityTest, TravelContentFoundationTest and the product-itinerary-gallery
and route-gallery-overflow Playwright specs. Review the actual development
screenshots before approving protected preview; production approval is separate.
