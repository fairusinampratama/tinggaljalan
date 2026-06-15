import { CalendarDays, ChevronRight, Clock, MessageCircle, Route as RouteIcon } from 'lucide-react';
import { Link, Navigate, useParams } from 'react-router-dom';
import { NewsCardsSection } from '../components/sections/NewsCardsSection';
import { Seo } from '../components/seo/Seo';
import { getNewsArticleBySlug, getRelatedNewsArticles, newsCategories } from '../data/news';
import { getRouteById } from '../data/routes';
import { useBooking } from '../context/BookingContext';
import { getLocalized } from '../utils/localization';
import { buildNewsArticleJsonLd, getNewsSeo } from '../utils/seo';

function formatDate(date, language = 'id') {
  return new Intl.DateTimeFormat(language === 'us' ? 'en-US' : 'id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(date));
}

export function NewsDetailPage() {
  const { articleSlug } = useParams();
  const { language, whatsappUrl } = useBooking();
  const article = getNewsArticleBySlug(articleSlug);

  if (!article) {
    return <Navigate to="/news" replace />;
  }

  const seo = getNewsSeo(article, language);
  const category = newsCategories.find((item) => item.value === article.category);
  const relatedRoutes = article.relatedRouteIds.map((routeId) => getRouteById(routeId)).filter(Boolean).slice(0, 3);
  const relatedArticles = getRelatedNewsArticles({
    destinationId: article.destinationId,
    excludeSlug: article.slug,
    limit: 3,
  });

  return (
    <>
      <Seo {...seo} jsonLd={buildNewsArticleJsonLd(article, language)} language={language} />
      <article className="px-4 pb-16 pt-28 sm:px-8 sm:pt-32 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <Link to="/news" className="inline-flex items-center gap-2 text-sm font-bold text-brandBlue transition hover:-translate-y-0.5 hover:text-brandDark">
            <ChevronRight className="h-4 w-4 rotate-180" />
            {language === 'id' ? 'Kembali ke Berita & Panduan' : 'Back to News & Guides'}
          </Link>

          <div className="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div>
              <div className="flex flex-wrap items-center gap-2 text-xs font-bold text-brandMuted">
                <span className="rounded-full bg-brandBlue/10 px-3 py-1 text-brandBlue">
                  {getLocalized(category?.label, language)}
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

              <h1 className="mt-4 max-w-4xl font-display text-3xl font-bold leading-tight text-brandDark sm:text-5xl">
                {getLocalized(article.title, language)}
              </h1>
              <p className="mt-5 max-w-3xl text-base font-semibold leading-8 text-brandMuted">
                {getLocalized(article.excerpt, language)}
              </p>

              <div className="mt-7 overflow-hidden rounded-2xl border border-brandLine bg-white shadow-soft">
                <img
                  src={article.coverImage}
                  alt={getLocalized(article.coverAlt, language)}
                  className="aspect-[16/9] w-full object-cover"
                />
              </div>

              <div className="mt-8 grid gap-8 lg:grid-cols-[220px_minmax(0,720px)]">
                <aside className="hidden lg:block">
                  <div className="sticky top-28 rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
                    <p className="text-xs font-bold uppercase tracking-[0.04em] text-brandBlue">
                      {language === 'id' ? 'Isi artikel' : 'In this article'}
                    </p>
                    <ol className="mt-4 grid gap-3 text-sm font-bold text-brandMuted">
                      {article.sections.map((section) => (
                        <li key={getLocalized(section.heading, language)}>
                          <a href={`#${getLocalized(section.heading, 'us').toLowerCase().replace(/[^a-z0-9]+/g, '-')}`} className="transition hover:text-brandBlue">
                            {getLocalized(section.heading, language)}
                          </a>
                        </li>
                      ))}
                    </ol>
                  </div>
                </aside>

                <div className="max-w-3xl">
                  {article.sections.map((section) => {
                    const id = getLocalized(section.heading, 'us').toLowerCase().replace(/[^a-z0-9]+/g, '-');

                    return (
                      <section key={id} id={id} className="scroll-mt-28 border-b border-brandLine py-7 first:pt-0">
                        <h2 className="text-2xl font-bold leading-tight text-brandDark sm:text-3xl">{getLocalized(section.heading, language)}</h2>
                        <p className="mt-4 text-base font-medium leading-8 text-brandMuted sm:text-lg">{getLocalized(section.body, language)}</p>
                      </section>
                    );
                  })}

                  <section className="mt-8 rounded-2xl border border-brandLine bg-white p-6 shadow-soft">
                    <p className="text-xs font-bold uppercase tracking-[0.04em] text-brandBlue">
                      {language === 'id' ? 'Butuh rekomendasi rute?' : 'Need route advice?'}
                    </p>
                    <h2 className="mt-2 text-2xl font-bold leading-tight text-brandDark">
                      {language === 'id' ? 'Chat tim Tinggal Jalan sebelum booking.' : 'Chat Tinggal Jalan before booking.'}
                    </h2>
                    <p className="mt-3 text-sm font-semibold leading-6 text-brandMuted">
                      {language === 'id'
                        ? 'Kirim destinasi, tanggal, jumlah peserta, dan gaya trip yang kamu mau. Tim akan bantu pilihkan rute paling masuk akal.'
                        : 'Send your destination, date, group size, and preferred travel style. The team will help suggest the most practical route.'}
                    </p>
                    <div className="mt-5 flex flex-col gap-3 sm:flex-row">
                      <a
                        href={whatsappUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#25D366] px-5 py-2.5 text-sm font-bold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-[#1fb457] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
                      >
                        <MessageCircle className="h-4 w-4" />
                        WhatsApp
                      </a>
                      <Link
                        to="/routes"
                        className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-brandLine bg-brandLight px-5 py-2.5 text-sm font-bold text-brandDark transition hover:-translate-y-0.5 hover:border-brandBlue hover:bg-white hover:text-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
                      >
                        <RouteIcon className="h-4 w-4" />
                        {language === 'id' ? 'Lihat rute' : 'View routes'}
                      </Link>
                    </div>
                  </section>
                </div>
              </div>
            </div>

            <aside className="lg:sticky lg:top-28 lg:self-start">
              <div className="rounded-2xl border border-brandLine bg-white p-5 shadow-soft">
                <p className="text-xs font-bold uppercase tracking-[0.04em] text-brandBlue">
                  {language === 'id' ? 'Rute terkait' : 'Related routes'}
                </p>
                <div className="mt-4 grid gap-3">
                  {relatedRoutes.map((route) => (
                    <Link
                      key={route.id}
                      to={`/routes/${route.id}`}
                      className="group rounded-xl border border-brandLine bg-brandLight p-4 transition hover:-translate-y-0.5 hover:border-brandBlue hover:bg-white"
                    >
                      <p className="flex items-center gap-2 text-sm font-bold text-brandDark transition group-hover:text-brandBlue">
                        <RouteIcon className="h-4 w-4 text-brandBlue" />
                        {getLocalized(route.title, language)}
                      </p>
                      <p className="mt-2 text-xs font-semibold leading-5 text-brandMuted">{getLocalized(route.bestFor, language)}</p>
                    </Link>
                  ))}
                </div>
                <Link
                  to="/routes"
                  className="mt-5 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-brandLine bg-white px-5 py-2.5 text-sm font-bold text-brandDark transition hover:-translate-y-0.5 hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
                >
                  {language === 'id' ? 'Lihat semua rute' : 'View all routes'}
                </Link>
              </div>
            </aside>
          </div>
        </div>
      </article>

      <NewsCardsSection
        articles={relatedArticles}
        language={language}
        eyebrow={language === 'id' ? 'Artikel terkait' : 'Related articles'}
        title={language === 'id' ? 'Baca panduan lainnya' : 'Read more guides'}
        text={language === 'id' ? 'Lanjutkan riset perjalanan dengan artikel lain yang masih relevan.' : 'Continue planning with other relevant articles.'}
        variant="compact"
      />
    </>
  );
}
