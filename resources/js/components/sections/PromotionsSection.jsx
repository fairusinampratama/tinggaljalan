import { useCallback, useEffect, useRef, useState } from 'react';
import { ArrowRight, Check, ChevronLeft, ChevronRight, Copy } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useBooking } from '../../context/BookingContext';
import { formatCurrency } from '../../utils/currency';
import { formatTravelDate } from '../../utils/date';
import { getLocalized } from '../../utils/localization';

function compactDiscount(value, currency) {
  const amount = Number(value);

  if (currency === 'IDR' && amount >= 1000) {
    return `Rp${Math.round(amount / 1000)}K`;
  }

  return formatCurrency(amount, currency).replace(/\.00$/, '');
}

function interpolate(template, replacements) {
  return Object.entries(replacements).reduce(
    (result, [token, value]) => result.replace(`{${token}}`, value),
    String(template ?? ''),
  );
}

async function copyText(value) {
  if (navigator.clipboard?.writeText) {
    try {
      await navigator.clipboard.writeText(value);
      return;
    } catch {
      // Fall back for browsers that expose Clipboard API but deny permission.
    }
  }

  const input = document.createElement('textarea');
  input.value = value;
  input.setAttribute('readonly', '');
  input.style.position = 'fixed';
  input.style.opacity = '0';
  document.body.appendChild(input);
  input.select();
  document.execCommand('copy');
  input.remove();
}

export function PromotionsSection({ items = [] }) {
  const { language, t } = useBooking();
  const trackRef = useRef(null);
  const copyTimerRef = useRef(null);
  const [copiedCode, setCopiedCode] = useState('');
  const [scrollState, setScrollState] = useState({ canGoBack: false, canGoForward: false, hasOverflow: false });
  const cardWidthClass = items.length === 1
    ? 'min-w-[calc(100%-0.75rem)] sm:min-w-[min(40rem,100%)]'
    : items.length === 2
      ? 'min-w-[88%] sm:min-w-[calc(50%-0.5rem)]'
      : 'min-w-[88%] sm:min-w-[calc(50%-0.5rem)] xl:min-w-[calc(33.333%-0.7rem)]';

  const updateScrollState = useCallback(() => {
    const track = trackRef.current;

    if (!track) return;

    const maximumScroll = Math.max(0, track.scrollWidth - track.clientWidth);
    setScrollState({
      canGoBack: track.scrollLeft > 4,
      canGoForward: track.scrollLeft < maximumScroll - 4,
      hasOverflow: maximumScroll > 4,
    });
  }, []);

  useEffect(() => {
    updateScrollState();
    const track = trackRef.current;
    const observer = new ResizeObserver(updateScrollState);

    if (track) observer.observe(track);
    window.addEventListener('resize', updateScrollState);

    return () => {
      observer.disconnect();
      window.removeEventListener('resize', updateScrollState);
      window.clearTimeout(copyTimerRef.current);
    };
  }, [items.length, updateScrollState]);

  if (!items.length) {
    return null;
  }

  function move(direction) {
    const track = trackRef.current;

    if (!track) return;

    track.scrollBy({ left: direction * track.clientWidth * 0.88, behavior: 'smooth' });
  }

  async function handleCopy(code) {
    try {
      await copyText(code);
      setCopiedCode(code);
      window.clearTimeout(copyTimerRef.current);
      copyTimerRef.current = window.setTimeout(() => setCopiedCode(''), 2200);
    } catch {
      setCopiedCode('');
    }
  }

  return (
    <section id="promotions" data-testid="promotions-section" className="overflow-hidden bg-subtle/65 px-4 py-10 sm:px-8 sm:py-12 lg:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="flex items-end justify-between gap-6">
          <div className="min-w-0">
            <p className="public-eyebrow text-accent">{t.promotionsEyebrow}</p>
            <h2 className="public-heading-section mt-2 text-primary">{t.promotionsTitle}</h2>
            <p className="public-copy mt-2 max-w-2xl">{t.promotionsText}</p>
          </div>
          <div className={`${scrollState.hasOverflow ? 'hidden sm:flex' : 'hidden'} shrink-0 gap-2`} aria-label={t.promotionsCarouselLabel}>
            <button
              type="button"
              aria-label={t.promotionsPrevious}
              disabled={!scrollState.canGoBack}
              onClick={() => move(-1)}
              className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line bg-white text-primary shadow-sm transition hover:border-secondary hover:text-secondary disabled:cursor-not-allowed disabled:opacity-35"
            >
              <ChevronLeft className="h-5 w-5" />
            </button>
            <button
              type="button"
              aria-label={t.promotionsNext}
              disabled={!scrollState.canGoForward}
              onClick={() => move(1)}
              className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line bg-white text-primary shadow-sm transition hover:border-secondary hover:text-secondary disabled:cursor-not-allowed disabled:opacity-35"
            >
              <ChevronRight className="h-5 w-5" />
            </button>
          </div>
        </div>

        <div
          ref={trackRef}
          onScroll={updateScrollState}
          className="mt-6 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        >
          {items.map((promotion) => {
            const isPercentage = promotion.discountType === 'percent';
            const discountLabel = isPercentage
              ? `${Number(promotion.discountValue).toLocaleString()}%`
              : compactDiscount(promotion.discountValue, promotion.currency ?? promotion.displayCurrency);
            const maximumText = promotion.maximumDiscount
              ? interpolate(t.promotionsUpTo, { amount: formatCurrency(promotion.maximumDiscount, promotion.displayCurrency) })
              : '';
            const expiryText = promotion.endsAt
              ? interpolate(t.promotionsUntil, { date: formatTravelDate(promotion.endsAt, language) })
              : '';
            const title = getLocalized(promotion.title, language);
            const description = getLocalized(promotion.description, language);
            const isCopied = copiedCode === promotion.code;

            return (
              <article
                key={promotion.code}
                data-testid="promotion-card"
                className={`flex ${cardWidthClass} snap-start flex-col rounded-2xl border border-secondary/25 bg-white p-4 shadow-soft`}
              >
                <div className="flex min-w-0 items-start gap-4">
                  <div className="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-2xl bg-primary px-1 text-center text-white shadow-sm">
                    <span className="text-lg font-extrabold leading-none">{discountLabel}</span>
                    <span className="mt-1 text-[9px] font-extrabold uppercase tracking-[0.14em]">{t.promotionsOff}</span>
                  </div>
                  <div className="min-w-0 flex-1">
                    <h3 className="line-clamp-1 text-base font-extrabold leading-6 text-ink">{title}</h3>
                    <p className="mt-1 line-clamp-1 text-xs leading-5 text-muted">{description}</p>
                    {(maximumText || expiryText) ? (
                      <div className="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-[10px] font-bold leading-4 text-muted">
                        {maximumText ? <span>{maximumText}</span> : null}
                        {expiryText ? <span>{expiryText}</span> : null}
                      </div>
                    ) : null}
                  </div>
                </div>

                <div className="mt-3 flex items-stretch gap-2">
                  <div className="flex min-w-0 flex-1 items-center justify-between gap-3 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-sm">
                    <code className="truncate font-sans text-xs font-extrabold tracking-[0.1em] text-primary">{promotion.code}</code>
                    <button
                      type="button"
                      data-testid={`copy-promotion-${promotion.code}`}
                      onClick={() => handleCopy(promotion.code)}
                      className="inline-flex shrink-0 items-center gap-1.5 text-xs font-extrabold text-secondary transition hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
                      aria-label={interpolate(t.promotionsCopyLabel, { code: promotion.code })}
                    >
                      {isCopied ? <Check className="h-3.5 w-3.5" /> : <Copy className="h-3.5 w-3.5" />}
                      <span>{isCopied ? t.promotionsCopied : t.promotionsCopy}</span>
                    </button>
                  </div>
                  <Link
                    to={promotion.ctaUrl}
                    aria-label={interpolate(t.promotionsViewLabel, { title })}
                    className="inline-flex w-12 shrink-0 items-center justify-center rounded-xl border border-secondary/25 bg-secondary/10 text-secondary transition hover:bg-secondary hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
                  >
                    <ArrowRight className="h-4 w-4" />
                  </Link>
                </div>
              </article>
            );
          })}
        </div>
        <p className="sr-only" aria-live="polite">
          {copiedCode ? interpolate(t.promotionsCopiedAnnouncement, { code: copiedCode }) : ''}
        </p>
      </div>
    </section>
  );
}
