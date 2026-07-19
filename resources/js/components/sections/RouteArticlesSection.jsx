import { Car, CheckCircle, Clock, FileText, ListChecks, MessageCircle, Users } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { formatCurrency } from '../../utils/currency';
import { getLocalized, getRegionConfig, localizeDuration } from '../../utils/localization';
import { iconSize, primaryButtonClass, secondaryButtonClass } from '../ui/styles';
import { SectionHeader } from '../ui/SectionHeader';
import { RatingDisplay } from '../ui/RatingDisplay';
import { ResponsiveImage } from '../ui/ResponsiveImage';

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
    <section id="articles" className={isCatalog ? 'bg-transparent' : 'public-section relative overflow-hidden bg-white'}>
      <div className={isCatalog ? '' : 'relative mx-auto max-w-7xl'}>
        {showHeader ? (
          <SectionHeader eyebrow={t.packagesEyebrow} title={t.packagesTitle}>
            {t.packagesText}
          </SectionHeader>
        ) : null}

        {routes.length ? (
          <div className={isCatalog ? 'grid items-stretch gap-5 lg:grid-cols-2' : 'grid gap-5 lg:grid-cols-3'}>
            {routes.map((item) => (
              <article
                key={item.id}
                role="link"
                tabIndex={0}
                className={`group cursor-pointer overflow-hidden rounded-xl border border-line bg-surface shadow-soft transition duration-300 hover:border-secondary/40 hover:shadow-xl hover:shadow-secondary/10 focus-within:-translate-y-1 focus-within:border-secondary/40 focus-within:shadow-xl focus-within:shadow-secondary/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
                  isCatalog ? 'flex h-full flex-col sm:grid sm:h-[21rem] sm:grid-cols-[42%_1fr]' : 'flex h-full flex-col'
                }`}
                onClick={() => openRouteArticle(item.id)}
                onKeyDown={(event) => {
                  if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openRouteArticle(item.id);
                  }
                }}
              >
                <div className={`${isCatalog ? 'h-full' : ''} overflow-hidden`}>
                  <ResponsiveImage
                    src={item.image}
                    alt={getLocalized(item.imageAlt, language) || getLocalized(item.title, language)}
                    className={`${isCatalog ? 'h-48 w-full sm:h-full' : 'h-52 w-full'} object-cover transition duration-500 group-hover:scale-105 group-focus-within:scale-105`}
                    sizes={isCatalog ? '(min-width: 1024px) 21vw, (min-width: 640px) 42vw, 100vw' : '(min-width: 1024px) 33vw, 100vw'}
                    width={1200}
                    height={750}
                  />
                </div>
                <div className={`${isCatalog ? 'flex min-w-0 flex-col p-4' : 'flex min-w-0 flex-1 flex-col p-5'}`}>
                  <span className={`${isCatalog ? 'block h-6 truncate' : 'self-start line-clamp-1'} rounded-full bg-secondary/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.04em] text-secondary transition duration-300 group-hover:bg-secondary group-hover:text-white group-focus-within:bg-secondary group-focus-within:text-white`}>
                    {getLocalized(isCatalog ? item.badge : item.tag, language)}
                  </span>
                  <h3 className={`${isCatalog ? 'mt-3 line-clamp-2 text-lg sm:min-h-[2.8rem]' : 'mt-4 line-clamp-2 text-[clamp(1.35rem,2.8vw,1.75rem)] lg:min-h-[4.4rem]'} font-display font-normal leading-tight transition duration-300 group-hover:text-secondary group-focus-within:text-secondary`}>
                    {getLocalized(item.title, language)}
                  </h3>
                  <RatingDisplay rating={item.rating} reviewCount={item.reviewCount} className={isCatalog ? 'mt-3 min-h-5' : 'mt-3'} />
                  {isCatalog ? (
                    <div className="mt-3 grid grid-rows-4 gap-2 text-xs font-semibold text-muted">
                      <div className="contents">
                        <span className="inline-flex min-w-0 items-center gap-1.5">
                          <Clock className="h-3.5 w-3.5 shrink-0 text-ink" />
                          <span className="truncate">{localizeDuration(item.duration, language)}</span>
                        </span>
                        <span className="inline-flex min-w-0 items-center gap-1.5">
                          <CheckCircle className="h-3.5 w-3.5 shrink-0 text-ink" />
                          <span className="truncate">{t.freeCancellation}</span>
                        </span>
                        <span className="inline-flex min-w-0 items-center gap-1.5">
                          <Car className="h-3.5 w-3.5 shrink-0 text-ink" />
                          <span className="truncate">{getLocalized(item.pickupLabel, language)}</span>
                        </span>
                        <span className="inline-flex min-w-0 items-center gap-1.5">
                          <Users className="h-3.5 w-3.5 shrink-0 text-ink" />
                          <span className="truncate">{getLocalized(item.groupType, language)}</span>
                        </span>
                      </div>
                    </div>
                  ) : (
                    <p className="mt-3 truncate text-xs font-bold uppercase tracking-[0.04em] text-secondary">
                      {getLocalized(item.destinationName, language)} / {getLocalized(item.category, language)}
                    </p>
                  )}
                  <p className={`${isCatalog ? 'mt-3 hidden' : 'mt-4 line-clamp-4 lg:min-h-24'} text-sm font-semibold leading-6 text-muted`}>
                    {getLocalized(isCatalog ? item.bestFor : item.intro, language)}
                  </p>
                  {isCatalog ? (
                    <div className="mt-auto border-t border-line pt-3 text-right">
                      <p className="text-xs font-semibold text-muted">{t.priceFrom}</p>
                      <p className="whitespace-nowrap text-2xl font-bold leading-none text-ink">
                        {formatCurrency(priceCurrency === 'USD' ? item.basePriceUsd : item.basePriceIdr ?? item.basePrice, priceCurrency)}
                      </p>
                      <p className="mt-1 whitespace-nowrap text-xs font-semibold text-muted">{t.perPerson}</p>
                      <p className="mt-1 truncate text-xs font-semibold text-muted">{t.priceVariesByGroupSize}</p>
                    </div>
                  ) : (
                    <div className="mt-auto pt-4 text-sm font-semibold text-muted">
                      <p>{t.priceFrom} {formatCurrency(priceCurrency === 'USD' ? item.basePriceUsd : item.basePriceIdr ?? item.basePrice, priceCurrency)}{t.perPax}</p>
                      <p className="mt-1 text-xs">{t.priceVariesByGroupSize}</p>
                    </div>
                  )}
                  <div className={`${isCatalog ? 'mt-4 hidden' : 'mt-4 grid grid-cols-2 gap-2'}`} onClick={(event) => event.stopPropagation()}>
                    <Link to={`/routes/${item.id}`} className={`${primaryButtonClass} min-w-0 px-3`}>
                      <FileText className={`${iconSize} shrink-0`} /> <span className="truncate">{t.readArticle}</span>
                    </Link>
                    <a href={whatsappUrl} target="_blank" rel="noreferrer" className={`${secondaryButtonClass} min-w-0 px-3`}>
                      <MessageCircle className={`${iconSize} shrink-0`} /> <span className="truncate">{t.askRoute}</span>
                    </a>
                  </div>
                </div>
              </article>
            ))}
          </div>
        ) : (
          <div className="rounded-xl border border-line bg-canvas p-8 text-center">
            <p className="text-xl font-bold text-ink">{emptyText}</p>
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
