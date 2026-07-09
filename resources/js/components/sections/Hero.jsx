import { ChevronLeft, ChevronRight, Compass, MessageCircle, Pause, Play } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useBooking } from '../../context/BookingContext';
import { useSlider } from '../../hooks/useSlider';
import { getLocalized } from '../../utils/localization';
import { Dropdown } from '../ui/Dropdown';
import { darkButtonClass, glassButtonClass, iconSize, primaryButtonClass } from '../ui/styles';

function formatCarouselLabel(template, current, total) {
  return (template || '')
    .replace('{current}', String(current))
    .replace('{total}', String(total));
}

function isInternalUrl(url) {
  return /^\/(?!\/)/.test(url || '');
}

function isAllowedExternalUrl(url) {
  return /^(?:https:\/\/|mailto:|tel:)/i.test(url || '');
}

function isSameContextExternalUrl(url) {
  return /^(?:mailto:|tel:)/i.test(url || '')
    || /^https:\/\/(?:[^/]+\.)?(?:wa\.me|whatsapp\.com)(?:\/|$)/i.test(url || '');
}

function HeroCta({ label, url, className }) {
  if (!label || !url) return null;

  if (isInternalUrl(url)) {
    return <Link to={url} className={className}>{label}</Link>;
  }

  if (!isAllowedExternalUrl(url)) return null;

  const sameContext = isSameContextExternalUrl(url);
  return (
    <a
      href={url}
      className={className}
      target={sameContext ? undefined : '_blank'}
      rel={sameContext ? undefined : 'noreferrer'}
    >
      {label}
    </a>
  );
}

function HeroSlideContent({ slide, language }) {
  const eyebrowText = getLocalized(slide.eyebrow, language);
  const headingText = getLocalized(slide.heading, language);
  const descriptionText = getLocalized(slide.description, language);
  const primaryCtaLabel = getLocalized(slide.primaryCtaLabel, language);
  const secondaryCtaLabel = getLocalized(slide.secondaryCtaLabel, language);

  let alignmentClass = 'items-start text-left';
  if (slide.textAlignment === 'center') alignmentClass = 'mx-auto items-center text-center';
  if (slide.textAlignment === 'right') alignmentClass = 'ml-auto items-end text-right';

  return (
    <div className={`relative flex h-full max-w-[600px] flex-col justify-center px-6 sm:px-12 ${alignmentClass}`}>
      {eyebrowText ? (
        <p className="mb-4 inline-flex border-l-2 border-accent px-4 text-[11px] font-semibold uppercase leading-5 tracking-[0.18em] text-accent sm:text-xs">
          {eyebrowText}
        </p>
      ) : null}
      {headingText ? (
        <h2 className="text-balance font-display text-4xl font-normal leading-[1.08] tracking-[-0.015em] text-white sm:text-[54px]">
          {headingText}
        </h2>
      ) : null}
      {descriptionText ? (
        <p className="mt-4 line-clamp-3 text-pretty text-sm font-semibold leading-relaxed text-white/90 sm:text-[17px]">
          {descriptionText}
        </p>
      ) : null}
      {(primaryCtaLabel && slide.primaryCtaUrl) || (secondaryCtaLabel && slide.secondaryCtaUrl) ? (
        <div className="mt-8 flex flex-wrap gap-3">
          <HeroCta label={primaryCtaLabel} url={slide.primaryCtaUrl} className={primaryButtonClass} />
          <HeroCta label={secondaryCtaLabel} url={slide.secondaryCtaUrl} className={glassButtonClass} />
        </div>
      ) : null}
    </div>
  );
}

function overlayClass(alignment) {
  if (alignment === 'right') {
    return 'absolute inset-0 bg-gradient-to-l from-black via-black/60 to-transparent';
  }
  if (alignment === 'center') {
    return 'absolute inset-0 bg-black';
  }
  return 'absolute inset-0 bg-gradient-to-r from-black via-black/60 to-transparent';
}

export function Hero({ t, language, booking, setBooking, whatsappUrl }) {
  const { publicData } = useBooking();
  const [tripStyle, setTripStyle] = useState('recommended');

  const slides = publicData.home?.heroSlides?.length ? publicData.home.heroSlides : [
    {
      id: 'fallback',
      desktopImage: '/images/hero-bromo.jpg',
      mobileImage: '/images/hero-bromo.jpg',
      imageAlt: { us: 'Bromo sunrise jeep route', id: 'Rute jip sunrise Bromo', cn: '?????????' },
      eyebrow: t.heroTag,
      heading: t.heroTitle,
      description: t.heroText,
      primaryCtaLabel: t.exploreRoutes,
      primaryCtaUrl: '/routes',
      secondaryCtaLabel: t.chat,
      secondaryCtaUrl: whatsappUrl,
      textAlignment: 'left',
      focalPosition: 'center',
      overlayStrength: 45,
    },
  ];

  const heroSettings = publicData.home?.heroSettings ?? {};
  const autoplayEnabled = Boolean(heroSettings.autoplayEnabled);
  const autoplayInterval = Math.min(15000, Math.max(5000, Number(heroSettings.autoplayInterval) || 8000));
  const preloadSources = useMemo(
    () => slides.map((slide) => ({ desktop: slide.desktopImage, mobile: slide.mobileImage || slide.desktopImage })),
    [slides],
  );
  const slider = useSlider({
    total: slides.length,
    autoplay: autoplayEnabled,
    autoplayInterval,
    preloadSources,
  });
  const hasMultiple = slides.length > 1;
  const activeSlideLabel = formatCarouselLabel(t.heroSlideStatus, slider.activeIndex + 1, slides.length);

  const destinationOptions = publicData.bookingOptions?.destinationOptions ?? [];
  const localizedStyleOptions = (publicData.routeStyles ?? []).map((option) => ({
    ...option,
    label: getLocalized(option.label, language),
  }));
  const selectedDestination = publicData.destinations?.find((destination) => destination.name === booking.destination);
  const routeSearchParams = new URLSearchParams();

  if (selectedDestination) {
    routeSearchParams.set('destination', selectedDestination.slug ?? selectedDestination.name);
  }
  if (tripStyle && tripStyle !== 'recommended') {
    routeSearchParams.set('style', tripStyle);
  }

  const routeSearchQuery = routeSearchParams.toString();
  const routeSearchPath = routeSearchQuery ? `/routes?${routeSearchQuery}` : '/routes';

  return (
    <section id="home" className="relative bg-canvas pt-0">
      <h1 className="sr-only">{t.heroTitle}</h1>
      <div
        className="group relative h-[500px] w-full overflow-hidden bg-primary sm:h-[580px]"
        tabIndex={hasMultiple ? 0 : undefined}
        role="region"
        aria-roledescription="carousel"
        aria-label={t.heroCarouselLabel}
        {...slider.handlers}
      >
        <span className="sr-only" aria-live="polite" aria-atomic="true">{activeSlideLabel}</span>
        <div
          className={`flex h-full w-full ease-in-out ${slider.prefersReducedMotion ? '' : 'transition-transform duration-700'}`}
          style={{ transform: `translateX(-${slider.activeIndex * 100}%)` }}
        >
          {slides.map((slide, index) => {
            const inactive = index !== slider.activeIndex;
            const overlayOpacity = Math.min(Math.max(slide.overlayStrength ?? 40, 0), 100) / 100;
            const slideLabel = formatCarouselLabel(t.heroSlideStatus, index + 1, slides.length);

            return (
              <div
                key={slide.id}
                className="relative h-full w-full shrink-0"
                role="group"
                aria-roledescription="slide"
                aria-label={slideLabel}
                aria-hidden={inactive}
                inert={inactive ? true : undefined}
              >
                <picture>
                  <source media="(min-width: 640px)" srcSet={slide.desktopImage} />
                  <img
                    src={slide.mobileImage || slide.desktopImage}
                    alt={getLocalized(slide.imageAlt, language) || ''}
                    className="absolute inset-0 h-full w-full object-cover"
                    style={{ objectPosition: slide.focalPosition || 'center' }}
                    loading={index === 0 ? 'eager' : 'lazy'}
                    fetchPriority={index === 0 ? 'high' : 'auto'}
                  />
                </picture>

                <div className={overlayClass(slide.textAlignment)} style={{ opacity: overlayOpacity }} />

                <div className="absolute inset-0 mx-auto max-w-7xl pt-16 sm:pt-20 lg:px-4">
                  <HeroSlideContent slide={slide} language={language} />
                </div>
              </div>
            );
          })}
        </div>

        {hasMultiple ? (
          <>
            <button
              type="button"
              onClick={slider.showPrevious}
              className="absolute left-4 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-white opacity-100 transition hover:bg-black/55 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:left-8 sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
              aria-label={t.heroPreviousSlide}
            >
              <ChevronLeft className="h-6 w-6" />
            </button>
            <button
              type="button"
              onClick={slider.showNext}
              className="absolute right-4 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-white opacity-100 transition hover:bg-black/55 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:right-8 sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
              aria-label={t.heroNextSlide}
            >
              <ChevronRight className="h-6 w-6" />
            </button>

            <div className="absolute bottom-5 left-0 right-0 z-10 flex items-center justify-center gap-2">
              <span className="mr-1 text-xs font-semibold tracking-wider text-white drop-shadow-md" aria-hidden="true">
                {String(slider.activeIndex + 1).padStart(2, '0')} / {String(slides.length).padStart(2, '0')}
              </span>
              {slides.map((slide, index) => (
                <button
                  key={`dot-${slide.id}`}
                  type="button"
                  onClick={() => slider.selectIndex(index)}
                  className="group/dot inline-flex h-11 w-11 items-center justify-center rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                  aria-label={`${t.heroGoToSlide} ${index + 1}`}
                  aria-current={index === slider.activeIndex ? 'true' : undefined}
                >
                  <span className={`h-1.5 rounded-full transition-all duration-300 ${index === slider.activeIndex ? 'w-6 bg-accent' : 'w-2 bg-white/60 group-hover/dot:bg-white'}`} />
                </button>
              ))}
              {autoplayEnabled ? (
                <button
                  type="button"
                  onClick={slider.toggleUserPaused}
                  className="ml-1 inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/30 bg-black/30 text-white transition hover:bg-black/55 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                  aria-label={slider.userPaused ? t.heroPlaySlides : t.heroPauseSlides}
                  aria-pressed={slider.userPaused}
                >
                  {slider.userPaused ? <Play className="h-4 w-4" /> : <Pause className="h-4 w-4" />}
                </button>
              ) : null}
            </div>
          </>
        ) : null}
      </div>

      <div className="relative z-30 mx-auto -mt-8 max-w-6xl px-4 pb-12 sm:-mt-12 sm:px-8 sm:pb-16 lg:px-10">
        <div className="rounded-xl border border-line/80 bg-surface/95 p-5 shadow-xl shadow-black/5 backdrop-blur sm:p-6">
          <p className="font-display text-xl font-normal text-primary sm:text-2xl">{t.searchTitle}</p>
          <p className="mt-1 max-w-2xl text-sm leading-6 text-muted">{t.findTripText}</p>
          <div className="mt-5 grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <Dropdown
              label={t.destinationFilterLabel}
              value={booking.destination}
              options={destinationOptions}
              onChange={(destination) => setBooking((current) => ({ ...current, destination }))}
            />
            <Dropdown label={t.styleLabel} value={tripStyle} options={localizedStyleOptions} onChange={setTripStyle} />
            <Link to={routeSearchPath} className={`${darkButtonClass} md:min-w-40`}>
              <Compass className={iconSize} /> {t.exploreTrips}
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}
