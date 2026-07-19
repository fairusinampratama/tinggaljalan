import { CalendarDays, ChevronRight, Clock, Compass, MessageCircle, Newspaper, Route as RouteIcon, Search, X } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { iconSize, secondaryButtonClass, whatsappButtonClass } from '../ui/styles';
import { SectionHeader } from '../ui/SectionHeader';
import { ResponsiveImage } from '../ui/ResponsiveImage';

const allValue = 'all';

function formatDate(date, locale = 'id-ID') {
  return new Intl.DateTimeFormat(locale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(date));
}


function getTagLabel(tag, language) {
  return typeof tag === 'string' ? tag : getLocalized(tag, language);
}

function getTagKey(tag, language, index) {
  return `${getTagLabel(tag, language) || 'tag'}-${index}`;
}

function getDestinationLabel(destinationId, destinations = []) {
  if (destinationId === 'indonesia') {
    return 'Indonesia';
  }

  return destinations.find((destination) => destination.slug === destinationId || destination.id === destinationId)?.name ?? destinationId;
}

function MetaRow({ article, language, reserveSpace = false }) {
  const { publicData, regionConfig } = useBooking();
  const category = publicData.categories?.find((item) => item.value === article.category);

  return (
    <div className={`flex min-w-0 flex-wrap content-start items-center gap-2 overflow-hidden text-xs font-bold text-muted ${reserveSpace ? 'sm:h-[3.25rem]' : ''}`}>
      <span className="inline-flex min-w-0 max-w-full items-center gap-1 rounded-full bg-secondary/10 px-3 py-1 text-secondary transition duration-300 group-hover:bg-secondary group-hover:text-white group-focus-within:bg-secondary group-focus-within:text-white">
        <Newspaper className="h-3.5 w-3.5 shrink-0" />
        <span className="truncate">{getLocalized(category?.label, language)}</span>
      </span>
      <span className="inline-flex min-w-0 max-w-full items-center gap-1 rounded-full bg-subtle px-3 py-1">
        <Compass className="h-3.5 w-3.5 shrink-0 text-secondary" />
        <span className="truncate">{getDestinationLabel(article.destinationId, publicData.destinations ?? [])}</span>
      </span>
      <span className="inline-flex shrink-0 items-center gap-1 whitespace-nowrap">
        <CalendarDays className="h-3.5 w-3.5 shrink-0 text-secondary" />
        {formatDate(article.publishedDate, regionConfig.locale)}
      </span>
      <span className="inline-flex shrink-0 items-center gap-1 whitespace-nowrap">
        <Clock className="h-3.5 w-3.5 shrink-0 text-secondary" />
        {getLocalized(article.readingTime, language)}
      </span>
    </div>
  );
}

function RelatedRouteChip({ article, language, compact = false }) {
  const { t } = useBooking();
  const routeId = article.relatedRouteIds?.[0];
  const route = null;

  if (!route) {
    return null;
  }

  return (
    <Link
      to={`/routes/${route.id}`}
      className={`inline-flex min-w-0 items-center gap-2 rounded-full border border-line bg-surface px-3 py-1.5 text-xs font-bold text-ink transition hover:border-secondary hover:bg-subtle hover:text-secondary ${compact ? 'max-w-full' : ''
        }`}
      onClick={(event) => event.stopPropagation()}
    >
      <RouteIcon className="h-3.5 w-3.5 shrink-0 text-secondary" />
      <span className="shrink-0">{t.newsRelated}</span>
      <span className="truncate">{getLocalized(route.title, language)}</span>
    </Link>
  );
}

export function NewsCard({ article, language = 'id', variant = 'standard' }) {
  const { t } = useBooking();
  const navigate = useNavigate();
  const isFeatured = variant === 'featured';
  const isCompact = variant === 'compact';
  const isHorizontal = variant === 'horizontal';
  const usesCompactSlots = isCompact || isHorizontal;
  const imageClass = isFeatured
    ? 'aspect-[16/10] h-full min-h-72'
    : isHorizontal || isCompact
      ? 'aspect-[16/10] h-48 sm:aspect-[4/3] sm:h-full sm:min-h-40'
      : 'aspect-[16/10] h-52';
  const titleClass = isFeatured
    ? 'line-clamp-3 text-3xl sm:text-4xl'
    : usesCompactSlots
      ? 'line-clamp-2 text-lg sm:min-h-[2.85rem]'
      : 'line-clamp-2 text-xl md:min-h-[3.15rem]';
  const excerptClass = isFeatured
    ? 'mt-4 line-clamp-3 text-sm leading-6'
    : usesCompactSlots
      ? 'mt-2 line-clamp-3 text-xs leading-5 sm:min-h-[3.75rem]'
      : 'mt-4 line-clamp-3 text-sm leading-6 md:min-h-[4.5rem]';
  const tagRowClass = isFeatured
    ? 'mt-4 flex max-h-16 min-h-8 flex-wrap items-start gap-2 overflow-hidden'
    : usesCompactSlots
      ? 'mt-4 flex flex-wrap items-start gap-2 overflow-hidden sm:h-7'
      : 'mt-4 flex flex-wrap items-start gap-2 overflow-hidden md:h-7';

  function openArticle() {
    navigate(`/news/${article.slug}`);
  }

  return (
    <article
      role="link"
      tabIndex={0}
      className={`group h-full cursor-pointer overflow-hidden rounded-xl border border-line bg-surface shadow-soft transition duration-300 hover:border-secondary/40 hover:shadow-xl hover:shadow-secondary/10 focus-within:-translate-y-1 focus-within:border-secondary/40 focus-within:shadow-xl focus-within:shadow-secondary/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${isHorizontal || isCompact ? 'flex flex-col sm:grid sm:grid-cols-[40%_1fr]' : isFeatured ? 'flex flex-col lg:grid lg:grid-cols-[1.08fr_0.92fr]' : 'flex flex-col'
        }`}
      onClick={openArticle}
      onKeyDown={(event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          openArticle();
        }
      }}
    >
      <div className="overflow-hidden bg-subtle">
        <ResponsiveImage
          src={article.coverImage}
          alt={getLocalized(article.coverAlt, language)}
          className={`${imageClass} w-full object-cover transition duration-500 group-hover:scale-105 group-focus-visible:scale-105`}
          sizes={isFeatured ? '(min-width: 1024px) 55vw, 100vw' : '(min-width: 1280px) 33vw, (min-width: 768px) 50vw, 100vw'}
          loading={isFeatured ? 'eager' : 'lazy'}
          fetchPriority={isFeatured ? 'high' : 'auto'}
          width={1200}
          height={750}
        />
      </div>
      <div className={`flex min-w-0 flex-1 flex-col ${isFeatured ? 'p-6 sm:p-7' : isCompact ? 'p-4' : 'p-5'}`}>
        <MetaRow article={article} language={language} reserveSpace={!isFeatured} />
        <h3 className={`mt-4 font-display font-normal leading-tight text-primary transition duration-300 group-hover:text-secondary group-focus-within:text-secondary ${titleClass}`}>
          {getLocalized(article.title, language)}
        </h3>
        <p className={`${excerptClass} font-medium text-muted`}>
          {getLocalized(article.excerpt, language)}
        </p>
        <div className={tagRowClass}>
          {(article.tags ?? []).slice(0, isFeatured ? 4 : 2).map((tag, index) => {
            const label = getTagLabel(tag, language);

            if (!label) {
              return null;
            }

            return (
              <span key={getTagKey(tag, language, index)} className="max-w-full truncate rounded-full border border-line bg-subtle px-3 py-1 text-xs font-bold text-muted">
                {label}
              </span>
            );
          })}
          <RelatedRouteChip article={article} language={language} compact={isCompact || isHorizontal} />
        </div>
        <div className="mt-auto flex pt-5">
          <div className="ml-auto inline-flex shrink-0 items-center gap-2 text-sm font-bold text-secondary transition">
            {t.newsReadArticle} <ChevronRight className={iconSize} />
          </div>
        </div>
      </div>
    </article>
  );
}

export function NewsCardGrid({ articles, language = 'id', variant = 'standard' }) {
  if (!articles.length) {
    return null;
  }

  return (
    <div className={variant === 'compact' ? 'grid items-stretch gap-5 lg:grid-cols-3' : 'grid items-stretch gap-5 md:grid-cols-2 xl:grid-cols-3'}>
      {articles.map((article) => (
        <NewsCard key={article.slug} article={article} language={language} variant={variant} />
      ))}
    </div>
  );
}

export function FeaturedNewsPanel({ featured, latest = [], language = 'id' }) {
  if (!featured) {
    return null;
  }

  return (
    <section className="grid gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
      <NewsCard article={featured} language={language} variant="featured" />
      <div className="grid auto-rows-fr gap-5">
        {latest.map((article) => (
          <NewsCard key={article.slug} article={article} language={language} variant="compact" />
        ))}
      </div>
    </section>
  );
}

export function NewsFilterBar({
  language = 'id',
  searchTerm,
  categoryFilter,
  destinationFilter,
  hasActiveFilters,
  resultCount,
  totalCount,
  onUpdate,
  onReset,
}) {
  const { publicData, t } = useBooking();
  const categories = publicData.categories ?? [];
  const destinations = publicData.destinations ?? [];

  return (
    <section className="rounded-2xl border border-line bg-surface p-4 sm:p-5">
      <div className="grid gap-4 lg:grid-cols-[1fr_320px] lg:items-center">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">
            {t.newsFilterDashboard}
          </p>
          <h2 className="mt-1 font-display text-2xl font-normal leading-tight text-primary">
            {t.newsFilterTitle}
          </h2>
          <p className="mt-2 text-sm font-semibold leading-6 text-muted">
            {resultCount} / {totalCount} {t.newsArticlesShown}
          </p>
        </div>
        <label className="relative block">
          <span className="sr-only">Search articles</span>
          <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink" />
          <input
            value={searchTerm}
            onChange={(event) => onUpdate({ search: event.target.value })}
            placeholder={t.newsSearchPlaceholder}
            className="min-h-12 w-full rounded-xl border border-line bg-canvas py-3 pl-11 pr-4 text-sm font-bold text-ink outline-none transition hover:border-secondary/40 focus:border-secondary focus:bg-surface"
          />
        </label>
      </div>

      <div className="mt-5 grid gap-4">
        <div className="flex gap-2 overflow-x-auto pb-1">
          {[{ value: allValue, label: { id: 'Semua', us: 'All', cn: '全部' } }, ...categories].map((category) => {
            const isActive = category.value === categoryFilter;

            return (
              <button
                key={category.value}
                type="button"
                className={`inline-flex min-h-10 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-bold transition ${isActive
                    ? 'border-secondary bg-secondary text-white'
                    : 'border-line bg-surface text-ink hover:border-secondary hover:bg-subtle hover:text-secondary'
                  }`}
                onClick={() => onUpdate({ category: category.value })}
              >
                <Newspaper className="h-4 w-4" />
                {getLocalized(category.label, language)}
              </button>
            );
          })}
        </div>
        <div className="flex flex-wrap gap-2">
          {[{ label: getLocalized({ id: 'Semua Destinasi', us: 'All Destinations', cn: '\u5168\u90e8\u76ee\u7684\u5730' }, language), value: allValue }, ...destinations.filter(d => d.slug !== 'indonesia' && d.id !== 'indonesia').map((destination) => ({ label: destination.name, value: destination.slug ?? destination.id }))].map((destination) => {
            const isActive = destination.value === destinationFilter;

            return (
              <button
                key={destination.value}
                type="button"
                className={`min-h-10 rounded-full border px-4 text-sm font-bold transition ${isActive
                    ? 'border-secondary bg-secondary text-white'
                    : 'border-line bg-surface text-ink hover:border-secondary hover:bg-subtle hover:text-secondary'
                  }`}
                onClick={() => onUpdate({ destination: destination.value })}
              >
                {destination.label}
              </button>
            );
          })}
          {hasActiveFilters ? (
            <button
              type="button"
              className="inline-flex min-h-10 items-center gap-2 rounded-full border border-line bg-surface px-4 text-sm font-bold text-ink transition hover:border-secondary hover:bg-subtle hover:text-secondary"
              onClick={onReset}
            >
              <X className="h-4 w-4" />
              Reset
            </button>
          ) : null}
        </div>
      </div>
    </section>
  );
}

export function NewsCtaBand({ language = 'id', whatsappUrl }) {
  const { t } = useBooking();
  return (
    <section className="overflow-hidden rounded-2xl bg-primary p-6 text-white shadow-soft sm:p-7 lg:flex lg:items-center lg:justify-between lg:gap-8">
      <div className="max-w-2xl">
        <p className="text-xs font-bold uppercase tracking-[0.04em] text-accent">
          {t.newsNeedAdviceTitle}
        </p>
        <h2 className="mt-2 font-display text-3xl font-normal leading-tight">
          {t.newsCtaHeading}
        </h2>
        <p className="mt-3 text-sm font-medium leading-6 text-white/70">
          {t.newsNeedAdviceText}
        </p>
      </div>
      <div className="mt-5 flex flex-col gap-3 sm:flex-row lg:mt-0">
        <Link
          to="/routes"
          className={secondaryButtonClass}
        >
          <RouteIcon className={iconSize} /> {t.viewRoutes}
        </Link>
        <a href={whatsappUrl} target="_blank" rel="noreferrer" className={whatsappButtonClass}>
          <MessageCircle className={iconSize} /> WhatsApp
        </a>
      </div>
    </section>
  );
}

export function NewsCardsSection({
  articles,
  language = 'id',
  title,
  eyebrow,
  text,
  showViewAll = false,
  variant = 'compact',
}) {
  const { t } = useBooking();

  if (!articles.length) {
    return null;
  }

  return (
    <section className="bg-canvas px-4 py-16 sm:px-8 lg:px-10">
      <div className="mx-auto max-w-7xl">
        {title ? (
          <SectionHeader eyebrow={eyebrow} title={title}>
            {text}
          </SectionHeader>
        ) : null}
        <NewsCardGrid articles={articles} language={language} variant={variant} />
        {showViewAll ? (
          <div className="mt-8 flex justify-center">
            <Link to="/news" className={secondaryButtonClass}>
              {t.newsViewAll} <ChevronRight className={iconSize} />
            </Link>
          </div>
        ) : null}
      </div>
    </section>
  );
}
