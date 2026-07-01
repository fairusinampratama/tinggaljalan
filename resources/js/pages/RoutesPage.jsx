import { useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { MapPin, Search, Sparkles, X } from 'lucide-react';
import { useSearchParams } from 'react-router-dom';
import { RouteArticlesSection } from '../components/sections/RouteArticlesSection';
import { Seo } from '../components/seo/Seo';
import { PageShell } from '../components/ui/PageShell';
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

function getStyleLabel(value, language, styles = []) {
  return getLocalized(styles.find((style) => style.value === value)?.label, language) || value;
}

function filterRoutes(routes, { search, destination, style, language }) {
  const normalizedSearch = search.trim().toLowerCase();

  return routes.filter((route) => {
    const matchesDestination = destination === allValue || route.destinationId === destination || route.destinationName?.us === destination;
    const matchesStyle = style === defaultStyle || route.styles?.includes(style);
    const searchable = [
      getLocalized(route.title, language),
      getLocalized(route.excerpt, language),
      getLocalized(route.destinationName, language),
      ...(route.highlights ?? []).map((item) => getLocalized(item, language)),
    ].join(' ').toLowerCase();
    const matchesSearch = !normalizedSearch || searchable.includes(normalizedSearch);

    return matchesDestination && matchesStyle && matchesSearch;
  });
}

function getRouteGroups(routes, { destination, style, search, t, language, destinationOptions, routeStyleOptions }) {
  if (search || style !== defaultStyle) {
    return [
      {
        id: 'matched',
        title: `${routes.length} ${t.matchedPackages}`,
        text: style !== defaultStyle ? `${t.styleLabel}: ${getStyleLabel(style, language, routeStyleOptions)}` : t.searchResultText,
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
            title: `${t.groupRecommendedTitle} ${getDestinationLabel(destination, t, destinationOptions)}`,
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
  const { props } = usePage();
  const { language, t, setSelectedRouteId, whatsappUrl, publicData } = useBooking();
  const [searchParams, setSearchParams] = useSearchParams();
  const allRoutes = props.routes ?? publicData.routes ?? [];
  const destinationOptions = (props.destinations ?? publicData.destinations ?? []).length
    ? (props.destinations ?? publicData.destinations).map((destination) => ({ value: destination.slug ?? destination.id, label: destination.name }))
    : [];
  const routeStyleOptions = publicData.routeStyles ?? [];
  const searchTerm = getQueryValue(searchParams, 'search', '');
  const destinationFilter = normalizeDestination(getQueryValue(searchParams, 'destination'), destinationOptions);
  const styleFilter = getQueryValue(searchParams, 'style', defaultStyle);

  const filteredRoutes = useMemo(
    () =>
      filterRoutes(allRoutes, {
        search: searchTerm,
        destination: destinationFilter,
        style: styleFilter,
        language,
      }),
    [allRoutes, destinationFilter, language, searchTerm, styleFilter],
  );
  const routeGroups = useMemo(
    () => getRouteGroups(filteredRoutes, { destination: destinationFilter, style: styleFilter, search: searchTerm, t, language, destinationOptions, routeStyleOptions }),
    [destinationFilter, destinationOptions, filteredRoutes, language, routeStyleOptions, searchTerm, styleFilter, t],
  );
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
      ? `${filteredRoutes.length} ${t.packagesReady}`
      : `${filteredRoutes.length} ${t.packagesMatchFor} ${destinationLabel}`;
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
    setSearchParams(nextParams, { replace: true });
  }

  function clearFilters() {
    setSearchParams({}, { replace: true });
  }

  return (
    <>
      <Seo title={seoTitle} description={seoDescription} path={seoPath} language={language} />
      <PageShell eyebrow="Paket & Rute" title={title}>
      <div className="mb-6 grid gap-4 lg:grid-cols-[1fr_320px] lg:items-end">
        <div>
          <p className="max-w-3xl text-base font-semibold leading-7 text-brandMuted">{intro}</p>
          <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-brandLine bg-brandLight px-4 py-2 text-sm font-bold text-brandDark">
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
          {[{ label: t.allDestinationsChip, value: allValue }, ...destinationOptions].map((destination) => {
            const isActive = destination.value === destinationFilter;

            return (
              <button
                key={destination.value}
                type="button"
                className={`inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-bold transition ${
                  isActive
                    ? 'border-brandBlue bg-brandBlue text-brandDark shadow-soft'
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
              className={`min-h-10 rounded-full border px-4 text-sm font-bold transition ${
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
            className="inline-flex min-h-10 items-center gap-2 rounded-full border border-brandLine bg-white px-4 text-sm font-bold text-brandDark transition hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue"
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
                  <h2 className="text-2xl font-bold leading-tight text-brandDark">{group.title}</h2>
                  <p className="mt-1 max-w-2xl text-sm font-semibold leading-6 text-brandMuted">{group.text}</p>
                </div>
                <p className="text-sm font-bold text-brandBlue">{group.routes.length} {t.packageCount}</p>
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
          <p className="text-2xl font-bold text-brandDark">{t.emptyPackagesTitle}</p>
          <p className="mx-auto mt-2 max-w-xl text-sm font-semibold leading-6 text-brandMuted">
            {t.emptyPackagesText}
          </p>
        </div>
      )}

      <p className="mt-8 text-center text-xs font-bold text-brandMuted">
        {allRoutes.length} {t.packageCount}. {t.routePriceNote}
      </p>
      </PageShell>
    </>
  );
}
