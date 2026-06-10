import { Car, Clock, FileText, ListChecks, MessageCircle, Users } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { formatCurrency } from '../../utils/currency';
import { getLocalized, getRegionConfig, localizeDuration, localizeList } from '../../utils/localization';
import { iconSize, primaryButtonClass, secondaryButtonClass } from '../ui/styles';
import { SectionHeader } from '../ui/SectionHeader';

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
    setSelectedRouteId(routeId);
    navigate(`/routes/${routeId}`);
  }

  return (
    <section id="articles" className={isCatalog ? 'bg-transparent' : 'relative overflow-hidden bg-white px-4 py-16 sm:px-8 lg:px-10'}>
      {!isCatalog ? (
        <>
          <div className="adventure-path -left-24 top-24 hidden opacity-80 lg:block" />
          <div className="adventure-blob bottom-10 right-10 h-36 w-36 opacity-55" />
          <div className="terrain-sweep bottom-24 right-[-5rem] hidden h-20 w-96 opacity-70 lg:block" />
        </>
      ) : null}
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
                className="group cursor-pointer overflow-hidden rounded-2xl border border-brandLine bg-brandLight shadow-soft transition duration-300 hover:-translate-y-1 hover:border-brandBlue/40 hover:bg-white hover:shadow-xl hover:shadow-brandBlue/10 focus-within:-translate-y-1 focus-within:border-brandBlue/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-brandBlue/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
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
                    className={`${isCatalog ? 'aspect-[4/3] w-full' : 'h-52 w-full'} object-cover transition duration-500 group-hover:scale-105 group-focus-within:scale-105`}
                  />
                </div>
                <div className={isCatalog ? 'p-4' : 'p-5'}>
                  <span className="rounded-full bg-brandBlue/10 px-3 py-1 text-xs font-extrabold uppercase tracking-[0.14em] text-brandBlue transition duration-300 group-hover:bg-brandBlue group-hover:text-white group-focus-within:bg-brandBlue group-focus-within:text-white">
                    {getLocalized(isCatalog ? item.badge : item.tag, language)}
                  </span>
                  <h3 className={`${isCatalog ? 'mt-3 text-xl' : 'mt-4 text-2xl'} font-extrabold leading-tight transition duration-300 group-hover:text-brandBlue group-focus-within:text-brandBlue`}>
                    {getLocalized(item.title, language)}
                  </h3>
                  {isCatalog ? (
                    <div className="mt-3 grid gap-2 text-xs font-black text-brandMuted">
                      <div className="flex flex-wrap gap-2">
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1">
                          <Clock className="h-3.5 w-3.5 text-brandBlue" />
                          {localizeDuration(item.duration, language)}
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1">
                          <Car className="h-3.5 w-3.5 text-brandBlue" />
                          {getLocalized(item.pickupLabel, language)}
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1">
                          <Users className="h-3.5 w-3.5 text-brandBlue" />
                          {getLocalized(item.groupType, language)}
                        </span>
                      </div>
                      <p className="text-brandBlue">{getLocalized(item.destinationName, language)} / {getLocalized(item.category, language)}</p>
                    </div>
                  ) : (
                    <p className="mt-3 text-xs font-extrabold uppercase tracking-[0.12em] text-brandBlue">
                      {getLocalized(item.destinationName, language)} / {getLocalized(item.category, language)}
                    </p>
                  )}
                  <p className={`${isCatalog ? 'mt-3 text-lg text-brandDark' : 'mt-2 text-sm text-brandMuted'} font-black`}>
                    {t.priceFrom} {formatCurrency(priceCurrency === 'USD' ? item.basePriceUsd : item.basePriceIdr ?? item.basePrice, priceCurrency)}{t.perPax}
                  </p>
                  <p className={`${isCatalog ? 'mt-2 max-h-12 overflow-hidden' : 'mt-4 min-h-20'} text-sm font-semibold leading-6 text-brandMuted`}>
                    {getLocalized(isCatalog ? item.bestFor : item.intro, language)}
                  </p>
                  {isCatalog ? (
                    <div className="mt-3 flex flex-wrap gap-2 text-xs font-black text-brandMuted">
                      {localizeList(item.highlights, language).slice(0, 2).map((highlight) => (
                        <span key={highlight} className="rounded-full bg-white px-2.5 py-1">
                          {highlight}
                        </span>
                      ))}
                      <span className="rounded-full bg-white px-2.5 py-1">{getLocalized(item.difficulty, language)}</span>
                    </div>
                  ) : null}
                  <div className="mt-5 flex flex-wrap gap-2" onClick={(event) => event.stopPropagation()}>
                    <Link to={`/routes/${item.id}`} className={primaryButtonClass} onClick={() => setSelectedRouteId(item.id)}>
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
            <p className="text-xl font-extrabold text-brandDark">{emptyText}</p>
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
