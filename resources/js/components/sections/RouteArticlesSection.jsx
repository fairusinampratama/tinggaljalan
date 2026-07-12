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
          <div className={isCatalog ? 'grid gap-4 md:grid-cols-2 xl:grid-cols-3' : 'grid gap-5 lg:grid-cols-3'}>
            {routes.map((item) => (
              <article
                key={item.id}
                role="link"
                tabIndex={0}
                className={`group cursor-pointer overflow-hidden rounded-xl border border-line bg-surface shadow-soft transition duration-300 hover:border-secondary/40 hover:shadow-xl hover:shadow-secondary/10 focus-within:-translate-y-1 focus-within:border-secondary/40 focus-within:shadow-xl focus-within:shadow-secondary/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
                  isCatalog ? 'flex flex-col sm:grid sm:grid-cols-[42%_1fr]' : ''
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
                  <ResponsiveImage
                    src={item.image}
                    alt={getLocalized(item.imageAlt, language) || getLocalized(item.title, language)}
                    className={`${isCatalog ? 'aspect-[16/10] h-48 w-full sm:h-full sm:min-h-52' : 'h-52 w-full'} object-cover transition duration-500 group-hover:scale-105 group-focus-within:scale-105`}
                    sizes={isCatalog ? '(min-width: 1280px) 33vw, (min-width: 768px) 50vw, 100vw' : '(min-width: 1024px) 33vw, 100vw'}
                    width={1200}
                    height={750}
                  />
                </div>
                <div className={`${isCatalog ? 'flex min-w-0 flex-col p-4' : 'p-5'}`}>
                  <span className="rounded-full bg-secondary/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.04em] text-secondary transition duration-300 group-hover:bg-secondary group-hover:text-white group-focus-within:bg-secondary group-focus-within:text-white">
                    {getLocalized(isCatalog ? item.badge : item.tag, language)}
                  </span>
                  <h3 className={`${isCatalog ? 'mt-3 line-clamp-3 text-lg' : 'mt-4 text-[clamp(1.35rem,2.8vw,1.75rem)]'} font-display font-normal leading-tight transition duration-300 group-hover:text-secondary group-focus-within:text-secondary`}>
                    {getLocalized(item.title, language)}
                  </h3>
                  <RatingDisplay rating={item.rating} reviewCount={item.reviewCount} className="mt-3" />
                  {isCatalog ? (
                    <div className="mt-3 grid gap-2 text-xs font-semibold text-muted">
                      <div className="grid gap-2">
                        <span className="inline-flex items-center gap-1.5">
                          <Clock className="h-3.5 w-3.5 text-ink" />
                          {localizeDuration(item.duration, language)}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                          <CheckCircle className="h-3.5 w-3.5 text-ink" />
                          {t.freeCancellation}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                          <Car className="h-3.5 w-3.5 text-ink" />
                          {getLocalized(item.pickupLabel, language)}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                          <Users className="h-3.5 w-3.5 text-ink" />
                          {getLocalized(item.groupType, language)}
                        </span>
                      </div>
                    </div>
                  ) : (
                    <p className="mt-3 text-xs font-bold uppercase tracking-[0.04em] text-secondary">
                      {getLocalized(item.destinationName, language)} / {getLocalized(item.category, language)}
                    </p>
                  )}
                  <p className={`${isCatalog ? 'mt-3 hidden' : 'mt-4 min-h-20'} text-sm font-semibold leading-6 text-muted`}>
                    {getLocalized(isCatalog ? item.bestFor : item.intro, language)}
                  </p>
                  {isCatalog ? (
                    <div className="mt-auto pt-5 text-right">
                      <p className="text-xs font-semibold text-muted">{t.priceFrom}</p>
                      <p className="text-2xl font-bold leading-none text-ink">
                        {formatCurrency(priceCurrency === 'USD' ? item.basePriceUsd : item.basePriceIdr ?? item.basePrice, priceCurrency)}
                      </p>
                      <p className="mt-1 text-xs font-semibold text-muted">{t.perPerson}</p>
                    </div>
                  ) : (
                    <p className="mt-2 text-sm font-semibold text-muted">
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
