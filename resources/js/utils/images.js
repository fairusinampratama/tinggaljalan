export const responsiveWidths = [480, 768, 960, 1200, 1600];

function stripExtension(path) {
  return path.replace(/\.[a-z0-9]+$/i, '');
}

function cleanPath(src) {
  return src ? src.split('?')[0] : '';
}

export function isGeneratedVariantAvailable(src) {
  const path = cleanPath(src);

  return path.startsWith('/images/') || path.startsWith('/storage/');
}

export function responsiveImagePath(src, width) {
  if (!src) return '';

  const cleanSrc = cleanPath(src);
  const withoutExtension = stripExtension(cleanSrc);

  if (cleanSrc.startsWith('/storage/')) {
    return `/storage/generated${withoutExtension}-${width}.webp`;
  }

  if (cleanSrc.startsWith('/images/')) {
    return `/images/generated${withoutExtension}-${width}.webp`;
  }

  return `/images/generated/${withoutExtension.replace(/^\/+/, '')}-${width}.webp`;
}

export function responsiveSrcSet(src) {
  if (!isGeneratedVariantAvailable(src)) return undefined;

  return responsiveWidths
    .map((width) => `${responsiveImagePath(src, width)} ${width}w`)
    .join(', ');
}