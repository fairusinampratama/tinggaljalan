import { useCallback, useEffect, useRef, useState } from 'react';

const SWIPE_THRESHOLD = 45;

export function useSlider({
  total,
  autoplay = false,
  autoplayInterval = 6000,
  preloadSources = [],
}) {
  const [activeIndex, setActiveIndex] = useState(0);
  const [userPaused, setUserPaused] = useState(false);
  const [interactionPaused, setInteractionPaused] = useState(false);
  const [prefersReducedMotion, setPrefersReducedMotion] = useState(false);
  const touchStartX = useRef(null);
  const didSwipeRef = useRef(false);
  const hasMultiple = total > 1;

  const showPrevious = useCallback(() => {
    if (!hasMultiple) return;
    setActiveIndex((current) => (current - 1 + total) % total);
  }, [hasMultiple, total]);

  const showNext = useCallback(() => {
    if (!hasMultiple) return;
    setActiveIndex((current) => (current + 1) % total);
  }, [hasMultiple, total]);

  const selectIndex = useCallback((index) => {
    if (!total) return;
    setActiveIndex(Math.min(Math.max(index, 0), total - 1));
  }, [total]);

  const toggleUserPaused = useCallback(() => {
    setUserPaused((current) => !current);
  }, []);

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

  const handleKeyDown = (event) => {
    if (event.target !== event.currentTarget) return;

    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      showPrevious();
    }
    if (event.key === 'ArrowRight') {
      event.preventDefault();
      showNext();
    }
  };

  useEffect(() => {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const updatePreference = () => setPrefersReducedMotion(mediaQuery.matches);
    updatePreference();
    mediaQuery.addEventListener?.('change', updatePreference);

    return () => mediaQuery.removeEventListener?.('change', updatePreference);
  }, []);

  const autoplayPaused = userPaused || interactionPaused || prefersReducedMotion;

  useEffect(() => {
    if (!autoplay || !hasMultiple || autoplayPaused) return undefined;

    const timer = window.setInterval(showNext, autoplayInterval);
    return () => window.clearInterval(timer);
  }, [activeIndex, autoplay, autoplayInterval, autoplayPaused, hasMultiple, showNext]);

  useEffect(() => {
    setActiveIndex((current) => Math.min(current, Math.max(total - 1, 0)));
  }, [total]);

  useEffect(() => {
    if (!hasMultiple || typeof Image === 'undefined') return;

    const indexes = [(activeIndex - 1 + total) % total, (activeIndex + 1) % total];
    indexes.forEach((index) => {
      const source = preloadSources[index];
      [source?.desktop, source?.mobile].filter(Boolean).forEach((url) => {
        const image = new Image();
        image.src = url;
      });
    });
  }, [activeIndex, hasMultiple, preloadSources, total]);

  return {
    activeIndex,
    selectIndex,
    showPrevious,
    showNext,
    userPaused,
    prefersReducedMotion,
    autoplayPaused,
    toggleUserPaused,
    handlers: {
      onTouchStart: handleTouchStart,
      onTouchEnd: handleTouchEnd,
      onKeyDown: handleKeyDown,
      onMouseEnter: () => setInteractionPaused(true),
      onMouseLeave: () => setInteractionPaused(false),
      onFocusCapture: () => setInteractionPaused(true),
      onBlurCapture: (event) => {
        if (!event.currentTarget.contains(event.relatedTarget)) {
          setInteractionPaused(false);
        }
      },
    },
    didSwipe: didSwipeRef.current,
  };
}
