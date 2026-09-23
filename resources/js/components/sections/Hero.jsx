import { Compass, MessageCircle } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useBooking } from '../../context/BookingContext';
import { useSlider } from '../../hooks/useSlider';
import { getLocalized } from '../../utils/localization';
import { Dropdown } from '../ui/Dropdown';
import { ResponsiveImage } from '../ui/ResponsiveImage';
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
    <div className={`relative flex h-full max-w-[min(34rem,calc(100%-2rem))] flex-col justify-end px-5 pb-28 pt-24 sm:max-w-[600px] sm:justify-center sm:px-12 sm:pb-0 sm:pt-0 ${alignmentClass}`}>
      {eyebrowText ? (
        <p className="mb-3 inline-flex border-l-2 border-accent px-3 text-[11px] font-semibold uppercase leading-5 tracking-[0.14em] text-accent sm:mb-4 sm:px-4 sm:text-xs sm:tracking-[0.18em]">
          {eyebrowText}
        </p>
      ) : null}
      {headingText ? (
        <h2 className="text-balance font-display text-[2.35rem] font-normal leading-[1.04] tracking-normal text-white sm:text-[54px] sm:leading-[1.08]">
          {headingText}
        </h2>
      ) : null}
      {descriptionText ? (
        <p className="mt-3 line-clamp-2 text-pretty text-sm font-semibold leading-relaxed text-white/90 sm:mt-4 sm:line-clamp-3 sm:text-[17px]">
          {descriptionText}
        </p>
      ) : null}
      {(primaryCtaLabel && slide.primaryCtaUrl) || (secondaryCtaLabel && slide.secondaryCtaUrl) ? (
        <div className="mt-6 flex flex-wrap gap-3 sm:mt-8">
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
  const slider = useSlider({
    total: slides.length,
    autoplay: autoplayEnabled,
    autoplayInterval,
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
        className="group relative h-[520px] w-full overflow-hidden bg-primary sm:h-[580px]"
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
                <ResponsiveImage
                  src={slide.mobileImage || slide.desktopImage}
                  desktopSrc={slide.desktopImage}
                  alt={getLocalized(slide.imageAlt, language) || ''}
                  className="absolute inset-0 h-full w-full object-cover"
                  style={{ objectPosition: slide.focalPosition || 'center' }}
                  sizes="100vw"
                  loading={index === 0 ? 'eager' : 'lazy'}
                  fetchPriority={index === 0 ? 'high' : 'auto'}
                  decoding={index === 0 ? 'sync' : 'async'}
                  width={1600}
                  height={900}
                />

                <div className={overlayClass(slide.textAlignment)} style={{ opacity: overlayOpacity }} />

                <div className="absolute inset-0 mx-auto max-w-7xl pt-16 sm:pt-20 lg:px-4">
                  <HeroSlideContent slide={slide} language={language} />
                </div>
              </div>
            );
          })}
        </div>

      </div>

      <div className="relative z-30 mx-auto -mt-7 max-w-6xl px-4 pb-12 sm:-mt-12 sm:px-8 sm:pb-16 lg:px-10">
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
