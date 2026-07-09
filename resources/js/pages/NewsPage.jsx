import { usePage, router } from '@inertiajs/react';
import { useSearchParams } from 'react-router-dom';
import { SearchX } from 'lucide-react';
import {
  FeaturedNewsPanel,
  NewsCardGrid,
  NewsCtaBand,
  NewsFilterBar,
} from '../components/sections/NewsCardsSection';
import { Seo } from '../components/seo/Seo';
import { Pagination } from '../components/ui/Pagination';
import { useBooking } from '../context/BookingContext';

const allValue = 'all';

function getQueryValue(searchParams, key, fallback = allValue) {
  return searchParams.get(key) || fallback;
}

export function NewsPage() {
  const { props } = usePage();
  const { language, whatsappUrl, publicData, t } = useBooking();
  const [searchParams] = useSearchParams();
  
  const paginatedArticles = props.articles ?? { data: [] };
  const articles = paginatedArticles.data ?? [];
  const totalArticles = paginatedArticles.total ?? articles.length;
  const featured = props.featured ?? null;
  const seo = props.seo ?? {};
  const latest = articles.filter((article) => article.slug !== featured?.slug).slice(0, 2);
  
  const searchTerm = getQueryValue(searchParams, 'search', '');
  const categoryFilter = getQueryValue(searchParams, 'category');
  const destinationFilter = getQueryValue(searchParams, 'destination');
  
  const hasActiveFilters = Boolean(searchTerm) || categoryFilter !== allValue || destinationFilter !== allValue;

  const firstRowArticles = articles.slice(0, 3);
  const remainingArticles = articles.slice(3);

  function updateQuery(nextValues) {
    const nextParams = new URLSearchParams(searchParams);

    Object.entries(nextValues).forEach(([key, value]) => {
      if (!value || value === allValue) {
        nextParams.delete(key);
      } else {
        nextParams.set(key, String(value));
      }
    });
    
    nextParams.delete('page'); // Reset to page 1 on new filter

    router.get('/news', Object.fromEntries(nextParams.entries()), {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  function clearFilters() {
    router.get('/news', {}, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  return (
    <>
      <Seo
        title={seo.title ?? 'Travel Guides & News | Tinggal Jalan'}
        description={seo.description ?? 'Travel guides, itinerary ideas, and route updates from Tinggal Jalan for Indonesia private trips.'}
        path="/news"
        image={seo.image ?? '/images/hero-bromo.jpg'}
        jsonLd={seo.json_ld}
        language={language}
      />
      <section className="px-4 pb-16 pt-28 sm:px-8 sm:pt-32 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <div className="mb-8 grid gap-5 lg:grid-cols-[minmax(0,0.95fr)_minmax(320px,0.55fr)] lg:items-end">
            <div>
              <p className="mb-3 text-xs font-bold uppercase tracking-[0.04em] text-secondary">{t.newsEyebrow}</p>
              <h1 className="font-display text-3xl font-normal leading-tight text-primary sm:text-5xl">
                {t.newsTitle}
              </h1>
              <p className="mt-5 max-w-3xl text-base font-medium leading-7 text-muted">
                {t.newsDescription}
              </p>
            </div>
            <div className="rounded-2xl border border-line bg-surface p-5 sm:p-6">
              <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">
                {t.newsStartHere}
              </p>
              <div className="mt-4 grid grid-cols-3 gap-3 text-center">
                {[
                  { value: (publicData.articles ?? []).length, label: t.newsArticlesCount },
                  { value: (publicData.destinations ?? []).length, label: t.newsDestinationsCount },
                  { value: 3, label: t.newsQuickPaths },
                ].map((item) => (
                  <div key={item.label} className="rounded-xl bg-canvas p-3">
                    <p className="text-2xl font-bold text-ink">{item.value}</p>
                    <p className="mt-1 text-xs font-semibold text-muted">{item.label}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>

          <FeaturedNewsPanel featured={featured} latest={latest} language={language} />

          <div className="mt-8">
            <NewsFilterBar
              language={language}
              searchTerm={searchTerm}
              categoryFilter={categoryFilter}
              destinationFilter={destinationFilter}
              hasActiveFilters={hasActiveFilters}
              resultCount={totalArticles}
              totalCount={(publicData.articles ?? []).length}
              onUpdate={updateQuery}
              onReset={clearFilters}
            />
          </div>

          <div className="mt-8">
            {articles.length ? (
              <>
                <div className="grid gap-8">
                  <NewsCardGrid articles={firstRowArticles} language={language} variant="standard" />
                  <NewsCtaBand language={language} whatsappUrl={whatsappUrl} />
                  {remainingArticles.length ? (
                    <NewsCardGrid articles={remainingArticles} language={language} variant="standard" />
                  ) : null}
                </div>
                <Pagination links={paginatedArticles.links} />
              </>
            ) : (
              <div className="grid gap-8">
                <div className="rounded-2xl border border-dashed border-line bg-canvas/50 px-8 py-16 text-center">
                  <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-secondary/10">
                    <SearchX className="h-6 w-6 text-secondary" />
                  </div>
                  <p className="text-xl font-bold text-ink">
                    {t.newsNoMatchTitle}
                  </p>
                  <p className="mx-auto mt-2 max-w-sm text-sm font-semibold leading-6 text-muted">
                    {t.newsNoMatchText}
                  </p>
                </div>
                <NewsCtaBand language={language} whatsappUrl={whatsappUrl} />
              </div>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
