import { Car, CheckCircle, Clock, FileText, ListChecks, MessageCircle, Users } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { formatCurrency } from '../../utils/currency';
import { getLocalized, getRegionConfig, localizeDuration } from '../../utils/localization';
import { iconSize, primaryButtonClass, secondaryButtonClass } from '../ui/styles';
import { SectionHeader } from '../ui/SectionHeader';
import { RatingDisplay } from '../ui/RatingDisplay';

export function RouteArticlesSection({
  t,
  routes = [],
  setSelectedRouteId,
  whatsappUrl,
  showHeader = true,
  showViewAll = false,
  emptyText = 'No routes match your search.',
  variant = 'featured',
}) {
  const navigate = useNavigate();
  const isCatalog = variant === 'catalog';
  const language = t.regionId ?? 'id';
  const region = getRegionConfig(language);
  const priceCurrency = region.currency;

  function openRouteArticle(routeId) {
    navigate(`/routes/${routeId}`);
  }

  return (
    <section id="articles" className={isCatalog ? 'bg-transparent' : 'relative overflow-hidden bg-white px-4 py-16 sm:px-8 lg:px-10'}>
      <div className={isCatalog ? '' : 'relative mx-auto max-w-7xl'}>
        {showHeader ? (
          <SectionHeader eyebrow={t.packagesEyebrow} title={t.packagesTitle}>
            {t.packagesText}
          </SectionHeader>
        ) : null}

        {routes.length ? (
          <div className={isCatalog ? 'grid gap-4 md:grid-cols-2 xl:grid-cols-3' : 'grid gap-5 lg:grid-cols-3'}>
            {routes.map((item) => (
              <article
                key={item.id}
                role="link"
                tabIndex={0}
                className={`group cursor-pointer overflow-hidden rounded-2xl border border-brandLine bg-white shadow-soft transition duration-300 hover:-translate-y-1 hover:border-brandBlue/40 hover:shadow-xl hover:shadow-brandBlue/10 focus-within:-translate-y-1 focus-within:border-brandBlue/40 focus-within:shadow-xl focus-within:shadow-brandBlue/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue ${
                  isCatalog ? 'grid grid-cols-[42%_1fr]' : ''
                }`}
                onClick={() => openRouteArticle(item.id)}
                onKeyDown={(event) => {
                  if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openRouteArticle(item.id);
                  }
                }}
              >
                <div className="overflow-hidden">
                  <img
                    src={item.image}
                    alt={getLocalized(item.imageAlt, language) || getLocalized(item.title, language)}
                    className={`${isCatalog ? 'h-full min-h-52 w-full' : 'h-52 w-full'} object-cover transition duration-500 group-hover:scale-105 group-focus-within:scale-105`}
                  />
                </div>
                <div className={`${isCatalog ? 'flex min-w-0 flex-col p-4' : 'p-5'}`}>
                  <span className="rounded-full bg-brandBlue/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.04em] text-brandBlue transition duration-300 group-hover:bg-brandBlue group-hover:text-brandDark group-focus-within:bg-brandBlue group-focus-within:text-brandDark">
                    {getLocalized(isCatalog ? item.badge : item.tag, language)}
                  </span>
                  <h3 className={`${isCatalog ? 'mt-3 line-clamp-3 text-lg' : 'mt-4 text-2xl'} font-bold leading-tight transition duration-300 group-hover:text-brandBlue group-focus-within:text-brandBlue`}>
                    {getLocalized(item.title, language)}
                  </h3>
                  <RatingDisplay rating={item.rating} reviewCount={item.reviewCount} className="mt-3" />
                  {isCatalog ? (
                    <div className="mt-3 grid gap-2 text-xs font-semibold text-brandMuted">
                      <div className="grid gap-2">
                        <span className="inline-flex items-center gap-1.5">
                          <Clock className="h-3.5 w-3.5 text-brandDark" />
                          {localizeDuration(item.duration, language)}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                          <CheckCircle className="h-3.5 w-3.5 text-brandDark" />
                          {t.freeCancellation}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                          <Car className="h-3.5 w-3.5 text-brandDark" />
                          {getLocalized(item.pickupLabel, language)}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                          <Users className="h-3.5 w-3.5 text-brandDark" />
                          {getLocalized(item.groupType, language)}
                        </span>
                      </div>
                    </div>
                  ) : (
                    <p className="mt-3 text-xs font-bold uppercase tracking-[0.04em] text-brandBlue">
                      {getLocalized(item.destinationName, language)} / {getLocalized(item.category, language)}
                    </p>
                  )}
                  <p className={`${isCatalog ? 'mt-3 hidden' : 'mt-4 min-h-20'} text-sm font-semibold leading-6 text-brandMuted`}>
                    {getLocalized(isCatalog ? item.bestFor : item.intro, language)}
                  </p>
                  {isCatalog ? (
                    <div className="mt-auto pt-5 text-right">
                      <p className="text-xs font-semibold text-brandMuted">{t.priceFrom}</p>
                      <p className="text-2xl font-extrabold leading-none text-brandDark">
                        {formatCurrency(priceCurrency === 'USD' ? item.basePriceUsd : item.basePriceIdr ?? item.basePrice, priceCurrency)}
                      </p>
                      <p className="mt-1 text-xs font-semibold text-brandMuted">{t.perPerson}</p>
                    </div>
                  ) : (
                    <p className="mt-2 text-sm font-semibold text-brandMuted">
                      {t.priceFrom} {formatCurrency(priceCurrency === 'USD' ? item.basePriceUsd : item.basePriceIdr ?? item.basePrice, priceCurrency)}{t.perPax}
                    </p>
                  )}
                  <div className={`${isCatalog ? 'mt-4 hidden' : 'mt-5'} flex flex-wrap gap-2`} onClick={(event) => event.stopPropagation()}>
                    <Link to={`/routes/${item.id}`} className={primaryButtonClass}>
                      <FileText className={iconSize} /> {t.readArticle}
                    </Link>
                    <a href={whatsappUrl} target="_blank" rel="noreferrer" className={secondaryButtonClass}>
                      <MessageCircle className={iconSize} /> {t.askRoute}
                    </a>
                  </div>
                </div>
              </article>
            ))}
          </div>
        ) : (
          <div className="rounded-2xl border border-brandLine bg-brandLight p-8 text-center">
            <p className="text-xl font-bold text-brandDark">{emptyText}</p>
          </div>
        )}

        {showViewAll ? (
          <div className="mt-8 flex justify-center">
            <Link to="/routes" className={secondaryButtonClass}>
              <ListChecks className={iconSize} /> {t.viewAllRoutes}
            </Link>
          </div>
        ) : null}
      </div>
    </section>
  );
}
