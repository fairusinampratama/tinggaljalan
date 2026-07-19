import { ChevronLeft, ChevronRight, Expand, X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

const SWIPE_THRESHOLD = 45;

function GalleryButton({ direction, label, onClick, lightbox = false }) {
  const Icon = direction === 'previous' ? ChevronLeft : ChevronRight;
  const position = direction === 'previous' ? 'left-3' : 'right-3';

  return (
    <button
      type="button"
      aria-label={label}
      onClick={(event) => {
        event.stopPropagation();
        onClick();
      }}
      className={`absolute top-1/2 z-10 -translate-y-1/2 ${position} inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 bg-white/90 text-primary shadow-lg transition hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent ${lightbox ? 'sm:h-12 sm:w-12' : ''}`}
    >
      <Icon className="h-5 w-5" aria-hidden="true" />
    </button>
  );
}

function Thumbnail({ image, index, activeIndex, alt, label, onSelect, imageRef, dark = false }) {
  const selected = index === activeIndex;

  return (
    <button
      ref={imageRef}
      type="button"
      aria-label={`${label} ${index + 1}`}
      aria-current={selected ? 'true' : undefined}
      onClick={() => onSelect(index)}
      className={`shrink-0 overflow-hidden rounded-lg border-2 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
        selected ? 'border-accent shadow-md' : dark ? 'border-white/20 opacity-65 hover:opacity-100' : 'border-transparent opacity-75 hover:border-line hover:opacity-100'
      }`}
    >
      <img src={image} alt="" loading={index === 0 ? 'eager' : 'lazy'} className="h-16 w-24 object-cover md:h-[72px] md:w-[104px]" />
      <span className="sr-only">{`${alt} ${index + 1}`}</span>
    </button>
  );
}

export function RouteGallery({ images, alt, labels }) {
  const safeImages = useMemo(() => (images ?? []).filter(Boolean), [images]);
  const [activeIndex, setActiveIndex] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const touchStartX = useRef(null);
  const didSwipeRef = useRef(false);
  const openButtonRef = useRef(null);
  const closeButtonRef = useRef(null);
  const desktopThumbnailRefs = useRef([]);
  const mobileThumbnailRefs = useRef([]);
  const lightboxThumbnailRefs = useRef([]);
  const total = safeImages.length;
  const hasMultiple = total > 1;

  const selectImage = useCallback((index) => {
    if (!total) return;
    setActiveIndex(Math.min(Math.max(index, 0), total - 1));
  }, [total]);

  const showPrevious = useCallback(() => {
    if (!hasMultiple) return;
    setActiveIndex((current) => (current - 1 + total) % total);
  }, [hasMultiple, total]);

  const showNext = useCallback(() => {
    if (!hasMultiple) return;
    setActiveIndex((current) => (current + 1) % total);
  }, [hasMultiple, total]);

  const handleTouchStart = (event) => {
    didSwipeRef.current = false;
    touchStartX.current = event.changedTouches[0]?.clientX ?? null;
  };

  const handleTouchEnd = (event) => {
    if (touchStartX.current === null) return;
    const endX = event.changedTouches[0]?.clientX ?? touchStartX.current;
    const distance = endX - touchStartX.current;
    touchStartX.current = null;

    if (Math.abs(distance) < SWIPE_THRESHOLD) return;
    didSwipeRef.current = true;
    if (distance > 0) showPrevious();
    else showNext();
  };

  useEffect(() => {
    setActiveIndex((current) => Math.min(current, Math.max(total - 1, 0)));
  }, [total]);

  useEffect(() => {
    if (!hasMultiple || typeof Image === 'undefined') return;
    const adjacentIndexes = [(activeIndex - 1 + total) % total, (activeIndex + 1) % total];
    adjacentIndexes.forEach((index) => {
      const preload = new Image();
      preload.src = safeImages[index];
    });
  }, [activeIndex, hasMultiple, safeImages, total]);

  useEffect(() => {
    [desktopThumbnailRefs, mobileThumbnailRefs, lightboxThumbnailRefs].forEach((collection) => {
      collection.current[activeIndex]?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
    });
  }, [activeIndex]);

  useEffect(() => {
    if (!lightboxOpen) return undefined;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    window.requestAnimationFrame(() => closeButtonRef.current?.focus());

    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        setLightboxOpen(false);
        return;
      }
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        showPrevious();
        return;
      }
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        showNext();
        return;
      }
      if (event.key !== 'Tab') return;

      const dialog = closeButtonRef.current?.closest('[role="dialog"]');
      const focusable = [...(dialog?.querySelectorAll('button:not([disabled])') ?? [])];
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = previousOverflow;
      openButtonRef.current?.focus();
    };
  }, [lightboxOpen, showNext, showPrevious]);

  if (!total) return null;

  const counter = `${activeIndex + 1} / ${total}`;
  const activeAlt = `${alt} ${activeIndex + 1}`;
  const thumbnailProps = (image, index, refs, dark = false) => ({
    image,
    index,
    activeIndex,
    alt,
    label: labels.thumbnail,
    onSelect: selectImage,
    imageRef: (element) => { refs.current[index] = element; },
    dark,
  });

  const lightbox = lightboxOpen && typeof document !== 'undefined' ? createPortal(
    <div
      className="fixed inset-0 z-[100] flex bg-primary/95 p-3 backdrop-blur-md sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-label={labels.dialog}
      onMouseDown={(event) => {
        if (event.target === event.currentTarget) setLightboxOpen(false);
      }}
    >
      <div className="relative mx-auto flex min-h-0 w-full max-w-7xl flex-col" onTouchStart={handleTouchStart} onTouchEnd={handleTouchEnd}>
        <div className="flex items-center justify-between pb-3 text-white">
          <span className="rounded-full bg-black/30 px-3 py-1.5 text-sm font-semibold" aria-live="polite">{counter}</span>
          <button
            ref={closeButtonRef}
            type="button"
            aria-label={labels.close}
            onClick={() => setLightboxOpen(false)}
            className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/25 bg-white/10 transition hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
          >
            <X className="h-6 w-6" aria-hidden="true" />
          </button>
        </div>
        <div
          className="relative min-h-0 flex-1"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) setLightboxOpen(false);
          }}
        >
          <img src={safeImages[activeIndex]} alt={activeAlt} className="h-full w-full object-contain" />
          {hasMultiple ? (
            <>
              <GalleryButton direction="previous" label={labels.previous} onClick={showPrevious} lightbox />
              <GalleryButton direction="next" label={labels.next} onClick={showNext} lightbox />
            </>
          ) : null}
        </div>
        {hasMultiple ? (
          <div className="mx-auto mt-4 flex max-w-full gap-2 overflow-x-auto pb-1">
            {safeImages.map((image, index) => (
              <Thumbnail key={`${image}-${index}`} {...thumbnailProps(image, index, lightboxThumbnailRefs, true)} />
            ))}
          </div>
        ) : null}
      </div>
    </div>,
    document.body,
  ) : null;

  return (
    <>
      <div className="mt-8 grid items-start gap-3 md:grid-cols-[112px_minmax(0,1fr)]">
        {hasMultiple ? (
          <div className="hidden max-h-[520px] flex-col gap-3 overflow-y-auto pr-1 md:flex">
            {safeImages.map((image, index) => (
              <Thumbnail key={`${image}-${index}`} {...thumbnailProps(image, index, desktopThumbnailRefs)} />
            ))}
          </div>
        ) : null}
        <div className="relative touch-pan-y overflow-hidden rounded-xl border border-line bg-surface shadow-soft" onTouchStart={handleTouchStart} onTouchEnd={handleTouchEnd}>
          <button
            ref={openButtonRef}
            type="button"
            aria-label={labels.open}
            onClick={() => {
              if (didSwipeRef.current) {
                didSwipeRef.current = false;
                return;
              }
              setLightboxOpen(true);
            }}
            onKeyDown={(event) => {
              if (event.key === 'ArrowLeft') {
                event.preventDefault();
                showPrevious();
              }
              if (event.key === 'ArrowRight') {
                event.preventDefault();
                showNext();
              }
            }}
            className="group block w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-accent"
          >
            <img src={safeImages[activeIndex]} alt={activeAlt} className="aspect-[16/10] w-full object-cover" />
            <span className="absolute right-3 top-3 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 bg-white/90 text-primary opacity-100 shadow-md transition sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-visible:opacity-100">
              <Expand className="h-4 w-4" aria-hidden="true" />
            </span>
          </button>
          <span className="absolute bottom-3 right-3 rounded-full bg-primary/80 px-3 py-1.5 text-xs font-semibold text-white" aria-live="polite">{counter}</span>
          {hasMultiple ? (
            <>
              <GalleryButton direction="previous" label={labels.previous} onClick={showPrevious} />
              <GalleryButton direction="next" label={labels.next} onClick={showNext} />
            </>
          ) : null}
        </div>
        {hasMultiple ? (
          <div className="flex gap-2 overflow-x-auto pb-1 md:hidden">
            {safeImages.map((image, index) => (
              <Thumbnail key={`${image}-${index}`} {...thumbnailProps(image, index, mobileThumbnailRefs)} />
            ))}
          </div>
        ) : null}
      </div>
      {lightbox}
    </>
  );
}
