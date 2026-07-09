import {
  AlertTriangle,
  CalendarDays,
  Car,
  CheckCircle,
  ChevronRight,
  Clock,
  Info,
  MapPin,
  MessageCircle,
  ShieldCheck,
  Sparkles,
  TicketCheck,
  Users,
  XCircle,
} from 'lucide-react';
import { formatCurrency } from '../../utils/currency';
import { availabilityForDate, todayIsoDate } from '../../utils/availability';
import { getLocalized, getRegionConfig, localizeDuration, localizeList } from '../../utils/localization';
import { cardHoverClass, iconSize, primaryButtonClass, secondaryButtonClass } from '../ui/styles';
import { RatingDisplay } from '../ui/RatingDisplay';
import { RouteGallery } from '../ui/RouteGallery';

function DetailList({ title, items, language, icon: Icon = CheckCircle, bulletIcon: BulletIcon }) {
  const visibleItems = localizeList(items, language);

  if (!visibleItems.length) {
    return null;
  }

  return (
    <section className={`rounded-xl border border-line bg-surface p-5 shadow-soft`}>
      <h3 className="flex items-center gap-2 text-lg font-bold text-ink">
        <Icon className="h-4 w-4 text-secondary" /> {title}
      </h3>
      <ul className="mt-4 grid gap-3 text-sm font-semibold leading-6 text-muted">
        {visibleItems.map((item) => (
          <li key={item} className="flex gap-3">
            {BulletIcon ? (
              <BulletIcon className="mt-1 h-4 w-4 shrink-0 text-secondary" />
            ) : (
              <div className="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-secondary/60" />
            )}
            <span>{item}</span>
          </li>
        ))}
      </ul>
    </section>
  );
}

function MetaPill({ icon: Icon, children }) {
  return (
    <div className="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-4 py-2 text-sm font-bold text-ink shadow-sm">
      <Icon className="h-4 w-4 text-secondary" />
      {children}
    </div>
  );
}

export function RouteDetailSection({ t, selectedArticle, whatsappUrl, onBookRoute }) {
  const language = t.regionId ?? 'id';
  const region = getRegionConfig(language);
  const priceCurrency = region.currency;
  const packageOption = selectedArticle.packageOptions?.[0] ?? {};
  const packagePrice = priceCurrency === 'USD'
    ? packageOption.basePriceUsd ?? selectedArticle.basePriceUsd
    : packageOption.basePriceIdr ?? selectedArticle.basePriceIdr ?? selectedArticle.basePrice;
  const gallery = selectedArticle.gallery?.length ? selectedArticle.gallery : [selectedArticle.image];
  const heroAlt = getLocalized(selectedArticle.imageAlt, language) || getLocalized(selectedArticle.title, language);
  const localizedTitle = getLocalized(selectedArticle.title, language);
  const currentAvailability = availabilityForDate(selectedArticle.availabilityRules, todayIsoDate());
  const activeClosure = currentAvailability.status === 'blocked' ? currentAvailability : null;

  return (
    <>
      <section id="route-detail" className="scroll-mt-24 bg-canvas px-4 pb-28 pt-28 sm:px-8 sm:pt-32 lg:px-10 lg:pb-10">
        <div className="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
          <article>
            <div className="flex flex-wrap items-center gap-2">
              <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">{t.routeDetailEyebrow}</p>
              <span className="rounded-full bg-secondary/10 px-3 py-1 text-xs font-bold text-secondary">
                {getLocalized(selectedArticle.badge, language)}
              </span>
            </div>

            <h1 className="mt-3 max-w-4xl font-display text-4xl font-normal leading-[1.08] tracking-[-0.015em] text-primary sm:text-4xl">
              {localizedTitle}
            </h1>
            <p className="mt-5 max-w-3xl text-sm font-semibold leading-7 text-muted sm:text-base">
              {getLocalized(selectedArticle.why, language)}
            </p>

            <div className="mt-4 flex flex-wrap items-center gap-3">
              <RatingDisplay rating={selectedArticle.rating} reviewCount={selectedArticle.reviewCount} size="md" />
              <span className="text-sm font-semibold text-muted">{getLocalized(selectedArticle.reviewSource, language)}</span>
            </div>

            <div className="mt-6 flex flex-wrap gap-2">
              <MetaPill icon={Clock}>{localizeDuration(selectedArticle.duration, language)}</MetaPill>
              <MetaPill icon={Car}>{getLocalized(selectedArticle.pickupLabel, language)}</MetaPill>
              <MetaPill icon={Users}>{getLocalized(selectedArticle.groupType, language)}</MetaPill>
              <MetaPill icon={MapPin}>{getLocalized(selectedArticle.destinationName, language)}</MetaPill>
            </div>

            {activeClosure ? (
              <div className="mt-8 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-900" role="alert">
                <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-red-600" aria-hidden="true" />
                <div>
                  <p className="font-bold">{t.temporaryClosure}</p>
                  {activeClosure.reason ? <p className="mt-1 text-sm leading-6">{activeClosure.reason}</p> : null}
                </div>
              </div>
            ) : null}

            <RouteGallery
              images={gallery}
              alt={heroAlt}
              labels={{
                previous: t.galleryPrevious,
                next: t.galleryNext,
                open: t.galleryOpen,
                close: t.galleryClose,
                thumbnail: t.galleryThumbnail,
                dialog: t.galleryDialog,
              }}
            />

            <section className="mt-8 rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-8">
              <h2 className="font-display text-3xl font-normal leading-[1.08] text-primary">{t.routeHighlights}</h2>
              <div className="mt-6 flex flex-wrap items-center gap-3">
                {localizeList(selectedArticle.highlights, language).map((highlight) => (
                  <div key={highlight} className="inline-flex items-center gap-2 rounded-full border border-line bg-canvas px-4 py-2 text-sm font-bold text-ink shadow-sm">
                    <Sparkles className="h-4 w-4 text-secondary" />
                    {highlight}
                  </div>
                ))}
              </div>
            </section>

            <section className="mt-8 rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-8">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">{t.packageOptions}</p>
                  <h2 className="mt-2 font-display text-3xl font-normal leading-[1.08] text-primary">
                    {getLocalized(packageOption.title, language) || localizedTitle}
                  </h2>
                  <p className="mt-3 max-w-2xl text-sm font-semibold leading-6 text-muted">
                    {getLocalized(packageOption.description, language) || getLocalized(selectedArticle.intro, language)}
                  </p>
                </div>
                <div className="rounded-xl bg-secondary/10 px-4 py-3 text-right">
                  <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">{t.fromPrice}</p>
                  <p className="font-display text-3xl font-normal leading-[1.08] text-primary">
                    {formatCurrency(packagePrice, priceCurrency)}
                  </p>
                  <p className="text-xs font-semibold text-muted">{t.perPerson}</p>
                </div>
              </div>
              {selectedArticle.pricing?.mode === 'tiered' && selectedArticle.pricing.tiers?.length ? (
                <div className="mt-5 overflow-hidden rounded-xl border border-line">
                  <div className="grid grid-cols-2 bg-subtle px-4 py-2 text-xs font-bold uppercase tracking-[0.04em] text-muted">
                    <span>{t.pax}</span>
                    <span className="text-right">{t.perPersonPrice}</span>
                  </div>
                  {selectedArticle.pricing.tiers.map((tier, index, arr) => (
                    <div key={tier.id} className="grid grid-cols-2 border-t border-line px-4 py-3 text-sm font-semibold">
                      <span>
                        {!tier.maxPax
                          ? `${tier.minPax}+`
                          : tier.minPax === tier.maxPax ? tier.minPax : `${tier.minPax}-${tier.maxPax}`} {t.pax}
                      </span>
                      <span className="text-right font-bold text-secondary">
                        {formatCurrency(priceCurrency === 'USD' ? tier.priceUsd : tier.priceIdr, priceCurrency)}
                      </span>
                    </div>
                  ))}
                </div>
              ) : null}
              <div className="mt-5 flex flex-wrap items-center gap-3">
                <MetaPill icon={CalendarDays}>{t.dateFlexible}</MetaPill>
                <MetaPill icon={Car}>{getLocalized(packageOption.pickupLabel, language)}</MetaPill>
                <MetaPill icon={Users}>{getLocalized(packageOption.groupType, language)}</MetaPill>
              </div>
            </section>

            {selectedArticle.addOns?.length ? (
              <section className="mt-8 rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-8">
                <div className="flex flex-wrap items-end justify-between gap-3">
                  <div>
                    <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">{t.addOns}</p>
                    <h2 className="mt-2 font-display text-3xl font-normal leading-[1.08] text-primary">{t.packageOptions}</h2>
                  </div>
                  <p className="text-xs font-bold text-muted">{priceCurrency}</p>
                </div>
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                  {selectedArticle.addOns.map((addOn) => {
                    const addOnPrice = priceCurrency === 'USD' ? addOn.priceUsd : addOn.priceIdr;

                    return (
                      <div key={addOn.id} className="flex h-full flex-col rounded-xl border border-line bg-canvas p-5 shadow-sm">
                        <p className="text-sm font-bold text-ink">{getLocalized(addOn.title, language)}</p>
                        <p className="mt-2 text-sm leading-relaxed text-muted">{getLocalized(addOn.description, language)}</p>
                        <p className="mt-auto pt-4 text-sm font-bold text-secondary">
                          {formatCurrency(addOnPrice, priceCurrency)} {addOn.pricing === 'perPax' ? t.perPax : t.perBooking}
                        </p>
                      </div>
                    );
                  })}
                </div>
              </section>
            ) : null}

            <div className="mt-8 grid gap-5 lg:grid-cols-2">
              <DetailList title={t.itinerary} items={selectedArticle.itinerary} language={language} icon={Clock} />
              <DetailList title={t.pickupDetails} items={selectedArticle.pickupDetails} language={language} icon={MapPin} />
              <DetailList title={t.includes} items={selectedArticle.includes} language={language} icon={CheckCircle} bulletIcon={CheckCircle} />
              <DetailList title={t.excludes} items={selectedArticle.excludes} language={language} icon={XCircle} bulletIcon={XCircle} />
              <DetailList title={t.goodToKnow} items={selectedArticle.goodToKnow} language={language} icon={Info} />
              <DetailList title={t.details} items={selectedArticle.details} language={language} icon={TicketCheck} />
            </div>

            <section className="mt-8 grid gap-5 lg:grid-cols-2">
              <div className="rounded-xl border border-line bg-surface p-5 shadow-soft">
                <h3 className="flex items-center gap-2 text-lg font-bold text-ink">
                  <ShieldCheck className="h-4 w-4 text-secondary" /> {t.cancellationPolicy}
                </h3>
                <p className="mt-3 text-sm font-semibold leading-6 text-muted">
                  {getLocalized(selectedArticle.policies?.cancellation, language)}
                </p>
              </div>
              <div className="rounded-xl border border-line bg-surface p-5 shadow-soft">
                <h3 className="flex items-center gap-2 text-lg font-bold text-ink">
                  <MessageCircle className="h-4 w-4 text-secondary" /> {t.confirmationPolicy}
                </h3>
                <p className="mt-3 text-sm font-semibold leading-6 text-muted">
                  {getLocalized(selectedArticle.policies?.confirmation, language)}
                </p>
              </div>
            </section>

            {selectedArticle.testimonials?.length ? (
              <section className="mt-8 rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <h2 className="font-display text-3xl font-normal leading-[1.08] text-primary">{t.travelerProof}</h2>
                    <p className="mt-2 text-sm font-semibold leading-6 text-muted">{t.verifiedNote}</p>
                  </div>
                  <div className="shrink-0 sm:pt-2">
                    <RatingDisplay rating={selectedArticle.rating} reviewCount={selectedArticle.reviewCount} />
                  </div>
                </div>
                <div className="mt-6 grid gap-4 md:grid-cols-2">
                  {selectedArticle.testimonials.map((item) => (
                    <figure key={`${item.name}-${getLocalized(item.meta, language)}`} className="flex flex-col rounded-xl border border-line bg-canvas p-5 shadow-sm">
                      <RatingDisplay rating={5} className="mb-4" />
                      <blockquote className="text-sm font-semibold leading-relaxed text-muted">
                        "{getLocalized(item.quote, language)}"
                      </blockquote>
                      <figcaption className="mt-auto pt-4 text-sm font-bold text-ink">
                        {item.name}
                        <span className="mt-1 block text-xs font-bold text-secondary">{getLocalized(item.meta, language)}</span>
                      </figcaption>
                    </figure>
                  ))}
                </div>
              </section>
            ) : null}
          </article>

          <aside className="lg:sticky lg:top-28 lg:self-start">
            <div className="rounded-2xl border border-line bg-surface p-5 shadow-soft">
              <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">{t.selectedPackage}</p>
              <h2 className="mt-2 font-display text-3xl font-normal leading-[1.08] text-primary">
                {getLocalized(packageOption.title, language) || localizedTitle}
              </h2>
              <div className="mt-5">
                <p className="text-xs font-bold uppercase tracking-[0.04em] text-muted">{t.fromPrice}</p>
                <p className="mt-1 font-display text-4xl font-normal leading-[1.08] text-primary">
                  {formatCurrency(packagePrice, priceCurrency)}
                </p>
                <p className="mt-1 text-sm font-semibold text-muted">{t.perPerson}</p>
              </div>
              <div className="mt-5 grid gap-3 text-sm font-semibold text-muted">
                <div className="flex items-center justify-between gap-3">
                  <span className="inline-flex items-center gap-2"><Clock className="h-4 w-4 text-secondary" />{t.itinerary}</span>
                  <span className="text-ink">{localizeDuration(selectedArticle.duration, language)}</span>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <span className="inline-flex items-center gap-2"><Car className="h-4 w-4 text-secondary" />{t.pickup}</span>
                  <span className="text-right text-ink">{getLocalized(selectedArticle.pickupLabel, language)}</span>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <span className="inline-flex items-center gap-2"><Users className="h-4 w-4 text-secondary" />{t.operator}</span>
                  <span className="text-right text-ink">{getLocalized(selectedArticle.operator, language)}</span>
                </div>
              </div>
              <div className="mt-5 grid gap-3">
                <a href={whatsappUrl} target="_blank" rel="noreferrer" className={primaryButtonClass}>
                  <MessageCircle className={iconSize} /> {t.askAvailability}
                </a>
                <button type="button" className={secondaryButtonClass} onClick={onBookRoute}>
                  {t.continueBooking} <ChevronRight className={iconSize} />
                </button>
              </div>
            </div>
          </aside>
        </div>
      </section>

      <div className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 p-3 shadow-2xl backdrop-blur lg:hidden">
        <div className="mx-auto flex max-w-7xl items-center gap-3">
          <div className="min-w-0 flex-1">
            <p className="truncate text-xs font-bold uppercase tracking-[0.04em] text-secondary">{t.fromPrice}</p>
            <p className="truncate text-lg font-bold text-ink">
              {formatCurrency(packagePrice, priceCurrency)} <span className="text-xs text-muted">{t.perPerson}</span>
            </p>
          </div>
          <a href={whatsappUrl} target="_blank" rel="noreferrer" className={primaryButtonClass}>
            <MessageCircle className={iconSize} /> {t.askRoute}
          </a>
        </div>
      </div>
    </>
  );
}
