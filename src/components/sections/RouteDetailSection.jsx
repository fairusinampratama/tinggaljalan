import {
  CalendarDays,
  Car,
  CheckCircle,
  ChevronRight,
  Clock,
  Info,
  MapPin,
  MessageCircle,
  ShieldCheck,
  Star,
  TicketCheck,
  Users,
} from 'lucide-react';
import { formatCurrency } from '../../utils/currency';
import { getLocalized, getRegionConfig, localizeDuration, localizeList } from '../../utils/localization';
import { cardHoverClass, iconSize, primaryButtonClass, secondaryButtonClass } from '../ui/styles';

function DetailList({ title, items, language, icon: Icon = CheckCircle }) {
  const visibleItems = localizeList(items, language);

  if (!visibleItems.length) {
    return null;
  }

  return (
    <section className={`rounded-2xl border border-brandLine bg-white p-5 shadow-soft ${cardHoverClass}`}>
      <h3 className="flex items-center gap-2 font-display text-xl font-black text-brandDark">
        <Icon className="h-4 w-4 text-brandBlue" /> {title}
      </h3>
      <ul className="mt-4 grid gap-3 text-sm font-semibold leading-6 text-brandMuted">
        {visibleItems.map((item) => (
          <li key={item} className="flex gap-2">
            <CheckCircle className="mt-1 h-3.5 w-3.5 shrink-0 text-brandBlue" /> {item}
          </li>
        ))}
      </ul>
    </section>
  );
}

function MetaPill({ icon: Icon, children }) {
  return (
    <span className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-black text-brandMuted shadow-sm">
      <Icon className="h-3.5 w-3.5 text-brandBlue" /> {children}
    </span>
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

  return (
    <>
      <section id="route-detail" className="scroll-mt-24 bg-brandLight px-4 pb-10 pt-28 sm:px-8 sm:pt-32 lg:px-10">
        <div className="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
          <article>
            <div className="flex flex-wrap items-center gap-2">
              <p className="text-xs font-black uppercase tracking-[0.18em] text-brandBlue">{t.routeDetailEyebrow}</p>
              <span className="rounded-full bg-brandBlue/10 px-3 py-1 text-xs font-black text-brandBlue">
                {getLocalized(selectedArticle.badge, language)}
              </span>
            </div>

            <h1 className="mt-3 max-w-4xl font-display text-4xl font-black leading-none text-brandDark sm:text-6xl">
              {localizedTitle}
            </h1>
            <p className="mt-5 max-w-3xl text-base font-semibold leading-8 text-brandMuted">
              {getLocalized(selectedArticle.why, language)}
            </p>

            <div className="mt-6 flex flex-wrap gap-2">
              <MetaPill icon={Clock}>{localizeDuration(selectedArticle.duration, language)}</MetaPill>
              <MetaPill icon={Car}>{getLocalized(selectedArticle.pickupLabel, language)}</MetaPill>
              <MetaPill icon={Users}>{getLocalized(selectedArticle.groupType, language)}</MetaPill>
              <MetaPill icon={MapPin}>{getLocalized(selectedArticle.destinationName, language)}</MetaPill>
            </div>

            <div className="mt-8 grid gap-3 md:grid-cols-[minmax(0,1fr)_180px]">
              <div className="overflow-hidden rounded-2xl border border-brandLine bg-white shadow-soft">
                <img src={gallery[0]} alt={heroAlt} className="aspect-[16/10] w-full object-cover" />
              </div>
              <div className="grid grid-cols-2 gap-3 md:grid-cols-1">
                {gallery.slice(1, 3).map((image) => (
                  <div key={image} className="overflow-hidden rounded-2xl border border-brandLine bg-white shadow-soft">
                    <img src={image} alt={heroAlt} className="h-full min-h-32 w-full object-cover" />
                  </div>
                ))}
              </div>
            </div>

            <section className="mt-8 rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
              <h2 className="font-display text-2xl font-black text-brandDark">{t.routeHighlights}</h2>
              <div className="mt-4 grid gap-3 sm:grid-cols-3">
                {localizeList(selectedArticle.highlights, language).map((highlight) => (
                  <div key={highlight} className="rounded-xl bg-brandLight p-4 text-sm font-black leading-6 text-brandDark">
                    {highlight}
                  </div>
                ))}
              </div>
            </section>

            <section className="mt-8 rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p className="text-xs font-black uppercase tracking-[0.16em] text-brandBlue">{t.packageOptions}</p>
                  <h2 className="mt-2 font-display text-2xl font-black text-brandDark">
                    {getLocalized(packageOption.title, language) || localizedTitle}
                  </h2>
                  <p className="mt-3 max-w-2xl text-sm font-semibold leading-6 text-brandMuted">
                    {getLocalized(packageOption.description, language) || getLocalized(selectedArticle.intro, language)}
                  </p>
                </div>
                <div className="rounded-xl bg-brandBlue/10 px-4 py-3 text-right">
                  <p className="text-xs font-black uppercase tracking-[0.14em] text-brandBlue">{t.fromPrice}</p>
                  <p className="font-display text-2xl font-black text-brandDark">
                    {formatCurrency(packagePrice, priceCurrency)}
                  </p>
                  <p className="text-xs font-black text-brandMuted">{t.perPerson}</p>
                </div>
              </div>
              <div className="mt-5 grid gap-3 text-sm font-black text-brandMuted sm:grid-cols-3">
                <MetaPill icon={CalendarDays}>{t.dateFlexible}</MetaPill>
                <MetaPill icon={TicketCheck}>{getLocalized(packageOption.pickupLabel, language)}</MetaPill>
                <MetaPill icon={Users}>{getLocalized(packageOption.groupType, language)}</MetaPill>
              </div>
            </section>

            <div className="mt-8 grid gap-5 lg:grid-cols-2">
              <DetailList title={t.itinerary} items={selectedArticle.itinerary} language={language} icon={Clock} />
              <DetailList title={t.pickupDetails} items={selectedArticle.pickupDetails} language={language} icon={MapPin} />
              <DetailList title={t.includes} items={selectedArticle.includes} language={language} icon={CheckCircle} />
              <DetailList title={t.excludes} items={selectedArticle.excludes} language={language} icon={Info} />
              <DetailList title={t.goodToKnow} items={selectedArticle.goodToKnow} language={language} icon={ShieldCheck} />
              <DetailList title={t.details} items={selectedArticle.details} language={language} icon={TicketCheck} />
            </div>

            <section className="mt-8 grid gap-5 lg:grid-cols-2">
              <div className="rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
                <h3 className="flex items-center gap-2 font-display text-xl font-black text-brandDark">
                  <ShieldCheck className="h-4 w-4 text-brandBlue" /> {t.cancellationPolicy}
                </h3>
                <p className="mt-3 text-sm font-semibold leading-6 text-brandMuted">
                  {getLocalized(selectedArticle.policies?.cancellation, language)}
                </p>
              </div>
              <div className="rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
                <h3 className="flex items-center gap-2 font-display text-xl font-black text-brandDark">
                  <MessageCircle className="h-4 w-4 text-brandBlue" /> {t.confirmationPolicy}
                </h3>
                <p className="mt-3 text-sm font-semibold leading-6 text-brandMuted">
                  {getLocalized(selectedArticle.policies?.confirmation, language)}
                </p>
              </div>
            </section>

            {selectedArticle.testimonials?.length ? (
              <section className="mt-8 rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
                <div className="flex flex-wrap items-end justify-between gap-3">
                  <div>
                    <h2 className="font-display text-2xl font-black text-brandDark">{t.travelerProof}</h2>
                    <p className="mt-2 text-xs font-bold leading-5 text-brandMuted">{t.verifiedNote}</p>
                  </div>
                  <div className="flex gap-1 text-brandBlue">
                    {[1, 2, 3, 4, 5].map((item) => (
                      <Star key={item} className="h-4 w-4 fill-current" />
                    ))}
                  </div>
                </div>
                <div className="mt-5 grid gap-4 md:grid-cols-2">
                  {selectedArticle.testimonials.map((item) => (
                    <figure key={`${item.name}-${getLocalized(item.meta, language)}`} className="rounded-xl bg-brandLight p-4">
                      <blockquote className="text-sm font-semibold leading-6 text-brandMuted">
                        "{getLocalized(item.quote, language)}"
                      </blockquote>
                      <figcaption className="mt-4 text-sm font-black text-brandDark">
                        {item.name}
                        <span className="block text-xs text-brandBlue">{getLocalized(item.meta, language)}</span>
                      </figcaption>
                    </figure>
                  ))}
                </div>
              </section>
            ) : null}
          </article>

          <aside className="lg:sticky lg:top-28 lg:self-start">
            <div className="rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
              <p className="text-xs font-black uppercase tracking-[0.16em] text-brandBlue">{t.selectedPackage}</p>
              <h2 className="mt-2 font-display text-2xl font-black text-brandDark">
                {getLocalized(packageOption.title, language) || localizedTitle}
              </h2>
              <div className="mt-5 rounded-xl bg-brandLight p-4">
                <p className="text-xs font-black uppercase tracking-[0.14em] text-brandMuted">{t.fromPrice}</p>
                <p className="mt-1 font-display text-3xl font-black text-brandDark">
                  {formatCurrency(packagePrice, priceCurrency)}
                </p>
                <p className="text-sm font-black text-brandMuted">{t.perPerson}</p>
              </div>
              <div className="mt-5 grid gap-3 text-sm font-black text-brandMuted">
                <div className="flex items-center justify-between gap-3">
                  <span className="inline-flex items-center gap-2"><Clock className="h-4 w-4 text-brandBlue" />{t.itinerary}</span>
                  <span>{localizeDuration(selectedArticle.duration, language)}</span>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <span className="inline-flex items-center gap-2"><Car className="h-4 w-4 text-brandBlue" />{t.pickup}</span>
                  <span className="text-right">{getLocalized(selectedArticle.pickupLabel, language)}</span>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <span className="inline-flex items-center gap-2"><Users className="h-4 w-4 text-brandBlue" />{t.operator}</span>
                  <span className="text-right">{getLocalized(selectedArticle.operator, language)}</span>
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
              <p className="mt-4 text-xs font-semibold leading-5 text-brandMuted">{t.routePriceNote}</p>
            </div>
          </aside>
        </div>
      </section>

      <div className="fixed inset-x-0 bottom-0 z-40 border-t border-brandLine bg-white/95 p-3 shadow-2xl backdrop-blur lg:hidden">
        <div className="mx-auto flex max-w-7xl items-center gap-3">
          <div className="min-w-0 flex-1">
            <p className="truncate text-xs font-black uppercase tracking-[0.12em] text-brandBlue">{t.fromPrice}</p>
            <p className="truncate font-display text-xl font-black text-brandDark">
              {formatCurrency(packagePrice, priceCurrency)} <span className="text-xs text-brandMuted">{t.perPerson}</span>
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
