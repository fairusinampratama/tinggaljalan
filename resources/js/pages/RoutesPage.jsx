import { usePage, router } from '@inertiajs/react';
import { MapPin, Search, Sparkles, X } from 'lucide-react';
import { useSearchParams } from 'react-router-dom';
import { RouteArticlesSection } from '../components/sections/RouteArticlesSection';
import { Seo } from '../components/seo/Seo';
import { PageShell } from '../components/ui/PageShell';
import { Pagination } from '../components/ui/Pagination';
import { useBooking } from '../context/BookingContext';
import { getLocalized } from '../utils/localization';

const allValue = 'all';
const defaultStyle = 'recommended';

function getQueryValue(searchParams, key, fallback = allValue) {
  return searchParams.get(key) || fallback;
}

function normalizeDestination(value, destinations = []) {
  if (!value || value === allValue) {
    return allValue;
  }

  return destinations.find((destination) => destination.value === value || destination.label === value)?.value ?? value;
}

function getDestinationLabel(value, t, destinations = []) {
  if (value === allValue) {
    return t.allDestinations;
  }

  return destinations.find((destination) => destination.value === value)?.label ?? value;
}

export function RoutesPage() {
  const { props } = usePage();
  const { language, t, setSelectedRouteId, whatsappUrl, publicData } = useBooking();
  const [searchParams] = useSearchParams();
  
  const paginatedRoutes = props.routes ?? { data: [] };
  const routes = paginatedRoutes.data ?? [];
  const totalRoutes = paginatedRoutes.total ?? routes.length;
  
  const destinationOptions = (props.destinations ?? publicData.destinations ?? []).length
    ? (props.destinations ?? publicData.destinations).map((destination) => ({ value: destination.slug ?? destination.id, label: destination.name }))
    : [];
  const routeStyleOptions = publicData.routeStyles ?? [];
  
  const searchTerm = getQueryValue(searchParams, 'search', '');
  const destinationFilter = normalizeDestination(getQueryValue(searchParams, 'destination'), destinationOptions);
  const styleFilter = getQueryValue(searchParams, 'style', defaultStyle);

  const hasActiveFilters = Boolean(searchTerm) || destinationFilter !== allValue || styleFilter !== defaultStyle;
  const destinationLabel = destinationOptions.find((destination) => destination.value === destinationFilter)?.label ?? getDestinationLabel(destinationFilter, t, destinationOptions);
  const title = destinationFilter === allValue ? t.routePageTitleAll : `${t.routePageTitleDestination} ${destinationLabel}`;
  const destinationCopy = {
    all: t.routeIntroAll,
    bromo: t.routeIntroBromo,
    jogja: t.routeIntroJogja,
    'tumpak-sewu': t.routeIntroTumpakSewu,
    medan: t.routeIntroMedan,
  };
  const intro = destinationCopy[destinationFilter] ?? destinationCopy.all;
  const summaryText =
    destinationFilter === allValue
      ? `${totalRoutes} ${t.packagesReady}`
      : `${totalRoutes} ${t.packagesMatchFor} ${destinationLabel}`;
  const seoTitle = destinationFilter === allValue
    ? 'Indonesia Tour Packages | Tinggal Jalan'
    : `${destinationLabel} Tour Packages | Tinggal Jalan`;
  const seoDescription = destinationFilter === allValue
    ? 'Compare private Indonesia tour packages for Bromo, Tumpak Sewu, Jogja, and Medan with clear itineraries, pickup options, prices, and traveler reviews.'
    : `${intro} Compare routes, prices, pickup options, reviews, and availability before sending a booking request to Tinggal Jalan.`;
  const seoPath = destinationFilter === allValue ? '/routes' : `/routes?destination=${encodeURIComponent(destinationFilter)}`;

  function updateQuery(nextValues) {
    const nextParams = new URLSearchParams(searchParams);

    Object.entries(nextValues).forEach(([key, value]) => {
      if (!value || value === allValue || (key === 'style' && value === defaultStyle)) {
        nextParams.delete(key);
      } else {
        nextParams.set(key, String(value));
      }
    });

    nextParams.delete('category');
    nextParams.delete('sort');
    nextParams.delete('page');
    
    router.get('/routes', Object.fromEntries(nextParams.entries()), {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  function clearFilters() {
    router.get('/routes', {}, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  return (
    <>
      <Seo title={seoTitle} description={seoDescription} path={seoPath} language={language} />
      <PageShell eyebrow={t.packagesAndRoutes} title={title}>
      <div className="mb-6 grid gap-4 lg:grid-cols-[1fr_320px] lg:items-end">
        <div>
          <p className="max-w-3xl text-base font-semibold leading-7 text-muted">{intro}</p>
          <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-line bg-canvas px-4 py-2 text-sm font-bold text-ink">
            <Sparkles className="h-4 w-4 text-secondary" />
            {summaryText}
          </div>
        </div>
        <label className="relative block">
          <span className="sr-only">{t.searchPackagesPlaceholder}</span>
          <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-secondary" />
          <input
            value={searchTerm}
            onChange={(event) => updateQuery({ search: event.target.value })}
            placeholder={t.searchPackagesPlaceholder}
            className="min-h-12 w-full rounded-xl border border-line bg-surface py-3 pl-11 pr-4 text-sm font-bold outline-none transition hover:border-secondary/40 focus:border-secondary"
          />
        </label>
      </div>

      <div className="sticky top-16 z-20 -mx-4 mb-5 border-y border-line bg-surface/95 px-4 py-3 backdrop-blur sm:-mx-8 sm:px-8 lg:static lg:mx-0 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0 lg:backdrop-blur-none">
        <div className="flex gap-2 overflow-x-auto pb-1">
          {[{ label: t.allDestinationsChip, value: allValue }, ...destinationOptions].map((destination) => {
            const isActive = destination.value === destinationFilter;

            return (
              <button
                key={destination.value}
                type="button"
                className={`inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-bold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
                  isActive
                    ? 'border-secondary bg-secondary text-white shadow-soft'
                    : 'border-line bg-surface text-ink hover:border-secondary hover:bg-subtle hover:text-secondary'
                }`}
                onClick={() => updateQuery({ destination: destination.value })}
              >
                <MapPin className="h-4 w-4" />
                {destination.label}
              </button>
            );
          })}
        </div>
      </div>

      <div className="mb-7 flex flex-wrap gap-2">
        {routeStyleOptions.map((style) => {
          const isActive = style.value === styleFilter;

          return (
            <button
              key={style.value}
              type="button"
              className={`min-h-10 rounded-full border px-4 text-sm font-bold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
                isActive
                  ? 'border-secondary bg-secondary text-white shadow-soft'
                  : 'border-line bg-canvas text-ink hover:border-secondary hover:bg-surface hover:text-secondary'
              }`}
              onClick={() => updateQuery({ style: style.value })}
            >
              {getLocalized(style.label, language)}
            </button>
          );
        })}
        {hasActiveFilters ? (
          <button
            type="button"
            className="inline-flex min-h-10 items-center gap-2 rounded-full border border-line bg-surface px-4 text-sm font-bold text-ink transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary hover:border-secondary hover:bg-subtle hover:text-secondary"
            onClick={clearFilters}
          >
            <X className="h-4 w-4" />
            {t.reset}
          </button>
        ) : null}
      </div>

      {routes.length ? (
        <>
          <RouteArticlesSection
            t={t}
            routes={routes}
            setSelectedRouteId={setSelectedRouteId}
            whatsappUrl={whatsappUrl}
            showHeader={false}
            variant="catalog"
          />
          <Pagination links={paginatedRoutes.links} />
        </>
      ) : (
        <div className="rounded-xl border border-line bg-canvas p-8 text-center">
          <p className="text-2xl font-bold text-ink">{t.emptyPackagesTitle}</p>
          <p className="mx-auto mt-2 max-w-xl text-sm font-semibold leading-6 text-muted">
            {t.emptyPackagesText}
          </p>
        </div>
      )}

      <p className="mt-8 text-center text-xs font-bold text-muted">
        {totalRoutes} {t.packageCount}. {t.routePriceNote}
      </p>
      </PageShell>
    </>
  );
}
