import { useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { useSearchParams } from 'react-router-dom';
import {
  FeaturedNewsPanel,
  NewsCardGrid,
  NewsCtaBand,
  NewsFilterBar,
} from '../components/sections/NewsCardsSection';
import { Seo } from '../components/seo/Seo';
import { useBooking } from '../context/BookingContext';
import { getLocalized } from '../utils/localization';

const allValue = 'all';

function getQueryValue(searchParams, key, fallback = allValue) {
  return searchParams.get(key) || fallback;
}

function filterArticles(articles, { search, category, destination, language }) {
  const normalizedSearch = search.trim().toLowerCase();

  return articles.filter((article) => {
    const matchesCategory = category === allValue || article.category === category;
    const matchesDestination = destination === allValue || article.destinationId === destination;
    const searchable = [
      getLocalized(article.title, language),
      getLocalized(article.excerpt, language),
      article.destinationName,
      ...(article.tags ?? []),
    ].join(' ').toLowerCase();
    const matchesSearch = !normalizedSearch || searchable.includes(normalizedSearch);

    return matchesCategory && matchesDestination && matchesSearch;
  });
}

export function NewsPage() {
  const { props } = usePage();
  const { language, whatsappUrl, publicData } = useBooking();
  const [searchParams, setSearchParams] = useSearchParams();
  const searchTerm = getQueryValue(searchParams, 'search', '');
  const categoryFilter = getQueryValue(searchParams, 'category');
  const destinationFilter = getQueryValue(searchParams, 'destination');
  const serverArticles = props.articles ?? publicData.articles ?? [];
  const serverFeatured = props.featured ?? serverArticles?.find((article) => article.isFeatured) ?? serverArticles?.[0];
  const featured = serverFeatured ?? null;
  const latest = serverArticles?.filter((article) => article.slug !== featured?.slug).slice(0, 2) ?? [];

  const filteredArticles = useMemo(
    () =>
      filterArticles(serverArticles, {
        search: searchTerm,
        category: categoryFilter,
        destination: destinationFilter,
        language,
      }),
    [categoryFilter, destinationFilter, language, searchTerm, serverArticles],
  );
  const hasActiveFilters = Boolean(searchTerm) || categoryFilter !== allValue || destinationFilter !== allValue;
  const firstRowArticles = filteredArticles.slice(0, 3);
  const remainingArticles = filteredArticles.slice(3);

  function updateQuery(nextValues) {
    const nextParams = new URLSearchParams(searchParams);

    Object.entries(nextValues).forEach(([key, value]) => {
      if (!value || value === allValue) {
        nextParams.delete(key);
      } else {
        nextParams.set(key, String(value));
      }
    });

    setSearchParams(nextParams, { replace: true });
  }

  function clearFilters() {
    setSearchParams({}, { replace: true });
  }

  return (
    <>
      <Seo
        title="Berita & Panduan Wisata | Tinggal Jalan"
        description="Baca berita, tips perjalanan, itinerary, dan panduan destinasi untuk Bromo, Jogja, Tumpak Sewu, Medan, dan private trip Indonesia."
        path="/news"
        image="/images/hero-bromo.jpg"
        language={language}
      />
      <section className="px-4 pb-16 pt-28 sm:px-8 sm:pt-32 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <div className="mb-8 grid gap-5 lg:grid-cols-[minmax(0,0.95fr)_minmax(320px,0.55fr)] lg:items-end">
            <div>
              <p className="mb-3 text-xs font-bold uppercase tracking-[0.04em] text-brandBlue">Berita & Panduan</p>
              <h1 className="font-display text-3xl font-bold leading-tight text-brandDark sm:text-5xl">
                {language === 'id'
                  ? 'Panduan wisata untuk memilih rute dengan lebih yakin'
                  : 'Travel guides for choosing routes with more confidence'}
              </h1>
              <p className="mt-5 max-w-3xl text-base font-semibold leading-7 text-brandMuted">
                {language === 'id'
                  ? 'Baca tips destinasi, ide itinerary, kabar rute, dan panduan booking sebelum lanjut ke paket atau chat WhatsApp.'
                  : 'Read destination tips, itinerary ideas, route updates, and booking guides before moving into packages or WhatsApp.'}
              </p>
            </div>
            <div className="rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
              <p className="text-xs font-bold uppercase tracking-[0.04em] text-brandBlue">
                {language === 'id' ? 'Mulai dari sini' : 'Start here'}
              </p>
              <div className="mt-4 grid grid-cols-3 gap-3 text-center">
                {[
                  { value: (publicData.articles ?? []).length, label: language === 'id' ? 'Artikel' : 'Articles' },
                  { value: (publicData.destinations ?? []).length, label: language === 'id' ? 'Destinasi' : 'Destinations' },
                  { value: 3, label: language === 'id' ? 'Aksi cepat' : 'Quick paths' },
                ].map((item) => (
                  <div key={item.label} className="rounded-xl bg-brandLight p-3">
                    <p className="text-2xl font-extrabold text-brandDark">{item.value}</p>
                    <p className="mt-1 text-xs font-bold text-brandMuted">{item.label}</p>
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
              resultCount={filteredArticles.length}
              totalCount={(publicData.articles ?? []).length}
              onUpdate={updateQuery}
              onReset={clearFilters}
            />
          </div>

          <div className="mt-8">
            {filteredArticles.length ? (
              <div className="grid gap-8">
                <NewsCardGrid articles={firstRowArticles} language={language} variant="standard" />
                <NewsCtaBand language={language} whatsappUrl={whatsappUrl} />
                {remainingArticles.length ? (
                  <NewsCardGrid articles={remainingArticles} language={language} variant="standard" />
                ) : null}
              </div>
            ) : (
              <div className="rounded-2xl border border-brandLine bg-brandLight p-8 text-center">
                <p className="text-2xl font-bold text-brandDark">
                  {language === 'id' ? 'Belum ada artikel yang cocok' : 'No matching articles yet'}
                </p>
                <p className="mx-auto mt-2 max-w-xl text-sm font-semibold leading-6 text-brandMuted">
                  {language === 'id'
                    ? 'Coba kata kunci, kategori, atau destinasi lain.'
                    : 'Try another keyword, category, or destination.'}
                </p>
                <div className="mt-6">
                  <NewsCtaBand language={language} whatsappUrl={whatsappUrl} />
                </div>
              </div>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
