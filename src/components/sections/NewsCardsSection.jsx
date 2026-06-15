import { CalendarDays, ChevronRight, Clock, Compass, MessageCircle, Newspaper, Route as RouteIcon, Search, X } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { getPrimaryRelatedRoute, newsCategories } from '../../data/news';
import { routeDestinations } from '../../data/routes';
import { getLocalized } from '../../utils/localization';
import { iconSize, secondaryButtonClass, whatsappButtonClass } from '../ui/styles';
import { SectionHeader } from '../ui/SectionHeader';

const allValue = 'all';

function formatDate(date, language = 'id') {
  return new Intl.DateTimeFormat(language === 'us' ? 'en-US' : 'id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(date));
}

function getDestinationLabel(destinationId) {
  if (destinationId === 'indonesia') {
    return 'Indonesia';
  }

  return routeDestinations.find((destination) => destination.value === destinationId)?.label ?? destinationId;
}

function MetaRow({ article, language }) {
  const category = newsCategories.find((item) => item.value === article.category);

  return (
    <div className="flex flex-wrap items-center gap-2 text-xs font-bold text-brandMuted">
      <span className="inline-flex items-center gap-1 rounded-full bg-brandBlue/10 px-3 py-1 text-brandBlue">
        <Newspaper className="h-3.5 w-3.5" />
        {getLocalized(category?.label, language)}
      </span>
      <span className="inline-flex items-center gap-1 rounded-full bg-brandLight px-3 py-1">
        <Compass className="h-3.5 w-3.5 text-brandBlue" />
        {getDestinationLabel(article.destinationId)}
      </span>
      <span className="inline-flex items-center gap-1">
        <CalendarDays className="h-3.5 w-3.5 text-brandBlue" />
        {formatDate(article.publishedDate, language)}
      </span>
      <span className="inline-flex items-center gap-1">
        <Clock className="h-3.5 w-3.5 text-brandBlue" />
        {getLocalized(article.readingTime, language)}
      </span>
    </div>
  );
}

function RelatedRouteChip({ article, language, compact = false }) {
  const route = getPrimaryRelatedRoute(article);

  if (!route) {
    return null;
  }

  return (
    <Link
      to={`/routes/${route.id}`}
      className={`inline-flex min-w-0 items-center gap-2 rounded-full border border-brandLine bg-white px-3 py-1.5 text-xs font-bold text-brandDark transition hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue ${
        compact ? 'max-w-full' : ''
      }`}
      onClick={(event) => event.stopPropagation()}
    >
      <RouteIcon className="h-3.5 w-3.5 shrink-0 text-brandBlue" />
      <span className="shrink-0">{language === 'id' ? 'Terkait:' : 'Related:'}</span>
      <span className="truncate">{getLocalized(route.title, language)}</span>
    </Link>
  );
}

export function NewsCard({ article, language = 'id', variant = 'standard' }) {
  const navigate = useNavigate();
  const isFeatured = variant === 'featured';
  const isCompact = variant === 'compact';
  const isHorizontal = variant === 'horizontal';
  const imageClass = isFeatured
    ? 'aspect-[16/10] h-full min-h-72'
    : isHorizontal || isCompact
      ? 'aspect-[4/3] h-full min-h-40'
      : 'aspect-[16/10] h-52';
  const titleClass = isFeatured ? 'text-3xl sm:text-4xl' : isCompact ? 'text-lg' : 'text-xl';

  function openArticle() {
    navigate(`/news/${article.slug}`);
  }

  return (
    <article
      role="link"
      tabIndex={0}
      className={`group cursor-pointer overflow-hidden rounded-2xl border border-brandLine bg-white shadow-soft transition duration-300 hover:-translate-y-1 hover:border-brandBlue/40 hover:shadow-xl hover:shadow-brandBlue/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue ${
        isHorizontal || isCompact ? 'grid grid-cols-[40%_1fr]' : ''
      } ${isFeatured ? 'lg:grid lg:grid-cols-[1.08fr_0.92fr]' : ''}`}
      onClick={openArticle}
      onKeyDown={(event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          openArticle();
        }
      }}
    >
      <div className="overflow-hidden bg-brandSoft">
        <img
          src={article.coverImage}
          alt={getLocalized(article.coverAlt, language)}
          className={`${imageClass} w-full object-cover transition duration-500 group-hover:scale-105 group-focus-visible:scale-105`}
        />
      </div>
      <div className={`flex min-w-0 flex-col ${isFeatured ? 'p-6 sm:p-7' : isCompact ? 'p-4' : 'p-5'}`}>
        <MetaRow article={article} language={language} />
        <h3 className={`mt-4 line-clamp-3 font-bold leading-tight text-brandDark transition group-hover:text-brandBlue ${titleClass}`}>
          {getLocalized(article.title, language)}
        </h3>
        <p className={`${isCompact ? 'mt-2 line-clamp-3 text-xs leading-5' : 'mt-4 line-clamp-3 text-sm leading-6'} font-semibold text-brandMuted`}>
          {getLocalized(article.excerpt, language)}
        </p>
        <div className="mt-4 flex flex-wrap gap-2">
          {article.tags.slice(0, isFeatured ? 4 : 2).map((tag) => (
            <span key={tag} className="rounded-full border border-brandLine bg-brandLight px-3 py-1 text-xs font-bold text-brandMuted">
              {tag}
            </span>
          ))}
        </div>
        <div className="mt-auto pt-5">
          <RelatedRouteChip article={article} language={language} compact={isCompact || isHorizontal} />
          <div className="mt-4 inline-flex items-center gap-2 text-sm font-bold text-brandBlue transition group-hover:translate-x-1">
            {language === 'id' ? 'Baca artikel' : 'Read article'} <ChevronRight className={iconSize} />
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
    <div className={variant === 'compact' ? 'grid gap-5 lg:grid-cols-3' : 'grid gap-5 md:grid-cols-2 xl:grid-cols-3'}>
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
      <div className="grid gap-5">
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
  return (
    <section className="rounded-2xl border border-brandLine bg-white p-4 shadow-soft sm:p-5">
      <div className="grid gap-4 lg:grid-cols-[1fr_320px] lg:items-center">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.04em] text-brandBlue">
            {language === 'id' ? 'Dashboard artikel' : 'Article dashboard'}
          </p>
          <h2 className="mt-1 text-2xl font-bold leading-tight text-brandDark">
            {language === 'id' ? 'Temukan panduan berdasarkan destinasi' : 'Find guides by destination'}
          </h2>
          <p className="mt-2 text-sm font-semibold leading-6 text-brandMuted">
            {resultCount} / {totalCount} {language === 'id' ? 'artikel ditampilkan' : 'articles shown'}
          </p>
        </div>
        <label className="relative block">
          <span className="sr-only">Search articles</span>
          <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-brandBlue" />
          <input
            value={searchTerm}
            onChange={(event) => onUpdate({ search: event.target.value })}
            placeholder={language === 'id' ? 'Cari berita, rute, atau destinasi' : 'Search news, routes, or destination'}
            className="min-h-12 w-full rounded-xl border border-brandLine bg-brandLight py-3 pl-11 pr-4 text-sm font-bold outline-none transition hover:border-brandBlue/40 focus:border-brandBlue focus:bg-white"
          />
        </label>
      </div>

      <div className="mt-5 grid gap-4">
        <div className="flex gap-2 overflow-x-auto pb-1">
          {newsCategories.map((category) => {
            const isActive = category.value === categoryFilter;

            return (
              <button
                key={category.value}
                type="button"
                className={`inline-flex min-h-10 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-bold transition ${
                  isActive
                    ? 'border-brandBlue bg-brandBlue text-white shadow-soft'
                    : 'border-brandLine bg-white text-brandDark hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue'
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
          {[{ label: 'Indonesia', value: allValue }, ...routeDestinations].map((destination) => {
            const isActive = destination.value === destinationFilter;

            return (
              <button
                key={destination.value}
                type="button"
                className={`min-h-10 rounded-full border px-4 text-sm font-bold transition ${
                  isActive
                    ? 'border-brandDark bg-brandDark text-white'
                    : 'border-brandLine bg-brandLight text-brandDark hover:border-brandBlue hover:bg-white hover:text-brandBlue'
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
              className="inline-flex min-h-10 items-center gap-2 rounded-full border border-brandLine bg-white px-4 text-sm font-bold text-brandDark transition hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue"
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
  return (
    <section className="rounded-2xl border border-brandLine bg-brandDark p-6 text-white shadow-soft sm:p-7 lg:flex lg:items-center lg:justify-between lg:gap-8">
      <div className="max-w-2xl">
        <p className="text-xs font-bold uppercase tracking-[0.04em] text-white/58">
          {language === 'id' ? 'Butuh rekomendasi rute?' : 'Need route advice?'}
        </p>
        <h2 className="mt-2 text-2xl font-bold leading-tight">
          {language === 'id' ? 'Pilih artikelnya, lalu cocokkan dengan paket yang paling masuk akal.' : 'Pick a guide, then match it with the most practical route.'}
        </h2>
        <p className="mt-3 text-sm font-semibold leading-6 text-white/68">
          {language === 'id'
            ? 'Tim Tinggal Jalan bisa bantu cek jadwal, pickup, dan opsi private trip sesuai artikel yang kamu baca.'
            : 'The Tinggal Jalan team can help check schedule, pickup, and private trip options based on the article you read.'}
        </p>
      </div>
      <div className="mt-5 flex flex-col gap-3 sm:flex-row lg:mt-0">
        <Link
          to="/routes"
          className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-brandDark shadow-soft transition hover:-translate-y-0.5 hover:bg-brandSoft focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
        >
          <RouteIcon className={iconSize} /> {language === 'id' ? 'Lihat rute' : 'View routes'}
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
  if (!articles.length) {
    return null;
  }

  return (
    <section className="bg-white px-4 py-16 sm:px-8 lg:px-10">
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
              {language === 'id' ? 'Lihat semua berita' : 'View all news'} <ChevronRight className={iconSize} />
            </Link>
          </div>
        ) : null}
      </div>
    </section>
  );
}
