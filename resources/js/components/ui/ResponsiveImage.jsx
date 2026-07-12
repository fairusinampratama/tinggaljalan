import { responsiveSrcSet } from '../../utils/images';

export function ResponsiveImage({
  src,
  desktopSrc,
  alt = '',
  className = '',
  sizes = '100vw',
  loading = 'lazy',
  fetchPriority = 'auto',
  decoding = 'async',
  width = 1200,
  height = 750,
  style,
}) {
  if (!src) return null;

  const desktopImage = desktopSrc || src;
  const mobileWebpSrcSet = responsiveSrcSet(src);
  const desktopWebpSrcSet = desktopImage !== src ? responsiveSrcSet(desktopImage) : undefined;

  return (
    <picture>
      {desktopWebpSrcSet ? (
        <source media="(min-width: 640px)" type="image/webp" srcSet={desktopWebpSrcSet} sizes={sizes} />
      ) : null}
      {desktopImage !== src ? <source media="(min-width: 640px)" srcSet={desktopImage} /> : null}
      {mobileWebpSrcSet ? <source type="image/webp" srcSet={mobileWebpSrcSet} sizes={sizes} /> : null}
      <img
        src={src}
        alt={alt}
        className={className}
        loading={loading}
        fetchPriority={fetchPriority}
        decoding={decoding}
        width={width}
        height={height}
        style={style}
      />
    </picture>
  );
}