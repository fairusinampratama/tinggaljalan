import { useMemo } from 'react';
import { MapPin, Search, Sparkles, X } from 'lucide-react';
import { useSearchParams } from 'react-router-dom';
import { RouteArticlesSection } from '../components/sections/RouteArticlesSection';
import { PageShell } from '../components/ui/PageShell';
import { getFilteredRoutes, routeArticles, routeDestinations, routeStyleOptions } from '../data/routes';
import { useBooking } from '../context/BookingContext';
import { getLocalized } from '../utils/localization';

const allValue = 'all';
const defaultStyle = 'recommended';

function getQueryValue(searchParams, key, fallback = allValue) {
  return searchParams.get(key) || fallback;
}

function normalizeDestination(value) {
  if (!value || value === allValue) {
    return allValue;
  }

  return routeDestinations.find((destination) => destination.value === value || destination.label === value)?.value ?? value;
}

function getDestinationLabel(value, t) {
  if (value === allValue) {
    return t.allDestinations;
  }

  return routeDestinations.find((destination) => destination.value === value)?.label ?? value;
}

function getStyleLabel(value, language) {
  return getLocalized(routeStyleOptions.find((style) => style.value === value)?.label, language) || value;
}

function getRouteGroups(routes, { destination, style, search, t, language }) {
  if (search || style !== defaultStyle) {
    return [
      {
        id: 'matched',
        title: `${routes.length} ${t.matchedPackages}`,
        text: style !== defaultStyle ? `${t.styleLabel}: ${getStyleLabel(style, language)}` : t.searchResultText,
        routes,
      },
    ];
  }

  const groupDefinitions =
    destination === allValue
      ? [
          {
            id: 'popular',
            title: t.groupPopularTitle,
            text: t.groupPopularText,
            match: (route) => route.styles.includes('recommended') || route.featured,
          },
          {
            id: 'short',
            title: t.groupShortTitle,
            text: t.groupShortText,
            match: (route) => route.duration === '1 Day' || route.duration === 'Half Day',
          },
          {
            id: 'family',
            title: t.groupFamilyTitle,
            text: t.groupFamilyText,
            match: (route) => route.styles.includes('family'),
          },
          {
            id: 'adventure',
            title: t.groupAdventureTitle,
            text: t.groupAdventureText,
            match: (route) => route.styles.includes('adventure') || route.styles.includes('waterfall'),
          },
          {
            id: 'multi-day',
            title: t.groupMultiDayTitle,
            text: t.groupMultiDayText,
            match: (route) => route.styles.includes('multi-day'),
          },
        ]
      : [
          {
            id: 'recommended',
            title: `${t.groupRecommendedTitle} ${getDestinationLabel(destination, t)}`,
            text: t.groupRecommendedText,
            match: (route) => route.styles.includes('recommended') || route.featured,
          },
          {
            id: 'easy',
            title: t.groupEasyTitle,
            text: t.groupEasyText,
            match: (route) => route.styles.includes('family') || route.difficulty.toLowerCase().includes('easy'),
          },
          {
            id: 'signature',
            title: t.groupSignatureTitle,
            text: t.groupSignatureText,
            match: (route) =>
              route.styles.includes('sunrise') ||
              route.styles.includes('waterfall') ||
              route.styles.includes('culture'),
          },
          {
            id: 'extended',
            title: t.groupExtendedTitle,
            text: t.groupExtendedText,
            match: (route) => route.styles.includes('multi-day') || route.category === 'Overland',
          },
        ];

  const usedRouteIds = new Set();

  const groups = groupDefinitions
    .map((group) => {
      const groupRoutes = routes.filter((route) => {
        if (usedRouteIds.has(route.id) || !group.match(route)) {
          return false;
        }

        usedRouteIds.add(route.id);
        return true;
      });

      return { ...group, routes: groupRoutes };
    })
    .filter((group) => group.routes.length);
  const remainingRoutes = routes.filter((route) => !usedRouteIds.has(route.id));

  return remainingRoutes.length
    ? [
        ...groups,
        {
          id: 'other',
          title: t.groupOtherTitle,
          text: t.groupOtherText,
          routes: remainingRoutes,
        },
      ]
    : groups;
}

export function RoutesPage() {
  const { language, t, setSelectedRouteId, whatsappUrl } = useBooking();
  const [searchParams, setSearchParams] = useSearchParams();
  const searchTerm = getQueryValue(searchParams, 'search', '');
  const destinationFilter = normalizeDestination(getQueryValue(searchParams, 'destination'));
  const styleFilter = getQueryValue(searchParams, 'style', defaultStyle);

  const filteredRoutes = useMemo(
    () =>
      getFilteredRoutes({
        search: searchTerm,
        destination: destinationFilter,
        style: styleFilter,
      }),
    [destinationFilter, searchTerm, styleFilter],
  );
  const routeGroups = useMemo(
    () => getRouteGroups(filteredRoutes, { destination: destinationFilter, style: styleFilter, search: searchTerm, t, language }),
    [destinationFilter, filteredRoutes, language, searchTerm, styleFilter, t],
  );
  const hasActiveFilters = Boolean(searchTerm) || destinationFilter !== allValue || styleFilter !== defaultStyle;
  const title = destinationFilter === allValue ? t.routePageTitleAll : `${t.routePageTitleDestination} ${getDestinationLabel(destinationFilter, t)}`;
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
      ? `${filteredRoutes.length} ${t.packagesReady}`
      : `${filteredRoutes.length} ${t.packagesMatchFor} ${getDestinationLabel(destinationFilter, t)}`;

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
    setSearchParams(nextParams, { replace: true });
  }

  function clearFilters() {
    setSearchParams({}, { replace: true });
  }

  return (
    <PageShell eyebrow="Paket & Rute" title={title}>
      <div className="mb-6 grid gap-4 lg:grid-cols-[1fr_320px] lg:items-end">
        <div>
          <p className="max-w-3xl text-base font-semibold leading-7 text-brandMuted">{intro}</p>
          <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-brandLine bg-brandLight px-4 py-2 text-sm font-extrabold text-brandDark">
            <Sparkles className="h-4 w-4 text-brandBlue" />
            {summaryText}
          </div>
        </div>
        <label className="relative block">
          <span className="sr-only">{t.searchPackagesPlaceholder}</span>
          <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-brandBlue" />
          <input
            value={searchTerm}
            onChange={(event) => updateQuery({ search: event.target.value })}
            placeholder={t.searchPackagesPlaceholder}
            className="min-h-12 w-full rounded-xl border border-brandLine bg-white py-3 pl-11 pr-4 text-sm font-bold outline-none transition hover:border-brandBlue/40 focus:border-brandBlue"
          />
        </label>
      </div>

      <div className="sticky top-16 z-20 -mx-4 mb-5 border-y border-brandLine bg-white/95 px-4 py-3 backdrop-blur sm:-mx-8 sm:px-8 lg:static lg:mx-0 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0 lg:backdrop-blur-none">
        <div className="flex gap-2 overflow-x-auto pb-1">
          {[{ label: t.allDestinationsChip, value: allValue }, ...routeDestinations].map((destination) => {
            const isActive = destination.value === destinationFilter;

            return (
              <button
                key={destination.value}
                type="button"
                className={`inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-black transition ${
                  isActive
                    ? 'border-brandBlue bg-brandBlue text-white shadow-soft'
                    : 'border-brandLine bg-white text-brandDark hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue'
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
              className={`min-h-10 rounded-full border px-4 text-sm font-black transition ${
                isActive
                  ? 'border-brandDark bg-brandDark text-white'
                  : 'border-brandLine bg-brandLight text-brandDark hover:border-brandBlue hover:bg-white hover:text-brandBlue'
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
            className="inline-flex min-h-10 items-center gap-2 rounded-full border border-brandLine bg-white px-4 text-sm font-black text-brandDark transition hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue"
            onClick={clearFilters}
          >
            <X className="h-4 w-4" />
            {t.reset}
          </button>
        ) : null}
      </div>

      {routeGroups.length ? (
        <div className="grid gap-9">
          {routeGroups.map((group) => (
            <section key={group.id}>
              <div className="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                  <h2 className="text-2xl font-extrabold leading-tight text-brandDark">{group.title}</h2>
                  <p className="mt-1 max-w-2xl text-sm font-semibold leading-6 text-brandMuted">{group.text}</p>
                </div>
                <p className="text-sm font-black text-brandBlue">{group.routes.length} {t.packageCount}</p>
              </div>
              <RouteArticlesSection
                t={t}
                routes={group.routes}
                setSelectedRouteId={setSelectedRouteId}
                whatsappUrl={whatsappUrl}
                showHeader={false}
                variant="catalog"
              />
            </section>
          ))}
        </div>
      ) : (
        <div className="rounded-2xl border border-brandLine bg-brandLight p-8 text-center">
          <p className="text-2xl font-extrabold text-brandDark">{t.emptyPackagesTitle}</p>
          <p className="mx-auto mt-2 max-w-xl text-sm font-semibold leading-6 text-brandMuted">
            {t.emptyPackagesText}
          </p>
        </div>
      )}

      <p className="mt-8 text-center text-xs font-bold text-brandMuted">
        {routeArticles.length} {t.packageCount}. {t.routePriceNote}
      </p>
    </PageShell>
  );
}
