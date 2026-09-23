import { usePage } from '@inertiajs/react';
import { CalendarDays, ChevronRight, Clock, Compass, MessageCircle, Route as RouteIcon } from 'lucide-react';
import { Link, Navigate, useParams } from 'react-router-dom';
import { NewsCardsSection } from '../components/sections/NewsCardsSection';
import { Seo } from '../components/seo/Seo';
import { useBooking } from '../context/BookingContext';
import { ResponsiveImage } from '../components/ui/ResponsiveImage';
import { getLocalized } from '../utils/localization';
import { buildNewsArticleJsonLd, getNewsSeo } from '../utils/seo';

function formatDate(date, locale = 'id-ID') {
  return new Intl.DateTimeFormat(locale, {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(date));
}

export function NewsDetailPage() {
  const { props } = usePage();
  const { articleSlug } = useParams();
  const { language, whatsappUrl, publicData, t, regionConfig } = useBooking();
  const article = props.article;

  if (!article) {
    return <Navigate to="/news" replace />;
  }

  const seo = props.seo ?? getNewsSeo(article, language);
  const category = publicData.categories?.find((item) => item.value === article.category);
  const relatedRoutes = props.relatedRoutes ?? [];
  const relatedArticles = props.relatedArticles ?? [];

  return (
    <>
      <Seo {...seo} jsonLd={seo.json_ld ?? buildNewsArticleJsonLd(article, language)} language={language} />
      <article className="px-4 pb-16 pt-28 sm:px-8 sm:pt-32 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div>
              <header className="mb-8">
                <div className="flex flex-wrap items-center gap-2 text-xs font-bold text-ink">
                  <span className="inline-flex items-center gap-1.5 rounded-full bg-secondary/10 px-3 py-1.5 text-secondary">
                    <Compass className="h-3.5 w-3.5" />
                    {getLocalized(category?.label, language)}
                  </span>
                  <span className="inline-flex items-center gap-1.5 rounded-full bg-surface px-3 py-1.5 shadow-sm">
                    <CalendarDays className="h-3.5 w-3.5 text-secondary" />
                    {formatDate(article.publishedDate, regionConfig.locale)}
                  </span>
                  <span className="inline-flex items-center gap-1.5 rounded-full bg-surface px-3 py-1.5 shadow-sm">
                    <Clock className="h-3.5 w-3.5 text-secondary" />
                    {getLocalized(article.readingTime, language)}
                  </span>
                </div>

                <h1 className="mt-5 max-w-4xl text-balance font-display text-4xl font-normal leading-[1.06] tracking-[-0.015em] text-primary sm:text-5xl lg:text-6xl">
                  {getLocalized(article.title, language)}
                </h1>
                <p className="mt-5 max-w-3xl text-pretty text-base font-medium leading-8 text-muted sm:text-lg">
                  {getLocalized(article.excerpt, language)}
                </p>
              </header>

              <div className="mb-10 overflow-hidden rounded-2xl">
                <ResponsiveImage
                  src={article.coverImage}
                  alt={getLocalized(article.coverAlt, language)}
                  className="aspect-[16/9] w-full object-cover"
                  sizes="(min-width: 1024px) 65vw, 100vw"
                  loading="eager"
                  fetchPriority="high"
                  width={1600}
                  height={900}
                />
              </div>

              <div className="max-w-3xl">
                {article.sections.map((section) => {
                  const id = getLocalized(section.heading, 'us').toLowerCase().replace(/[^a-z0-9]+/g, '-');

                  return (
                    <section key={id} id={id} className="scroll-mt-28 mb-10">
                      <h2 className="text-balance font-display text-2xl font-normal leading-[1.12] text-primary sm:text-3xl">{getLocalized(section.heading, language)}</h2>
                      <p className="mt-4 whitespace-pre-line text-pretty text-base font-medium leading-8 text-muted sm:text-lg">{getLocalized(section.body, language)}</p>
                    </section>
                  );
                })}

                <section className="mt-12 overflow-hidden rounded-2xl bg-primary p-6 text-white shadow-soft sm:p-8">
                  <p className="text-xs font-bold uppercase tracking-[0.08em]  text-accent">
                    {t.newsNeedAdviceTitle}
                  </p>
                  <h2 className="mt-2 font-display text-3xl font-normal leading-tight text-white">
                    {t.newsNeedAdviceHeading}
                  </h2>
                  <p className="mt-3 max-w-xl text-sm font-medium leading-7 text-white/70">
                    {t.newsNeedAdviceText}
                  </p>
                  <div className="mt-5 flex flex-col gap-3 sm:flex-row">
                    <a
                      href={whatsappUrl}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#25D366] px-5 py-2.5 text-sm font-bold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-[#1fb457] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
                    >
                      <MessageCircle className="h-4 w-4" />
                      WhatsApp
                    </a>
                    <Link
                      to="/routes"
                      className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/25 bg-surface px-5 py-2.5 text-sm font-bold text-ink transition hover:-translate-y-0.5 hover:border-secondary hover:bg-subtle hover:text-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
                    >
                      <RouteIcon className="h-4 w-4" />
                      {t.viewRoutes}
                    </Link>
                  </div>
                </section>
              </div>
            </div>

            <aside className="lg:sticky lg:top-28 lg:self-start flex flex-col gap-6">
              <div className="hidden lg:block rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">
                  {t.newsInThisArticle}
                </p>
                <ul className="mt-4 grid gap-3 text-sm font-bold text-muted">
                  {article.sections.map((section) => (
                    <li key={getLocalized(section.heading, language)}>
                      <a href={`#${getLocalized(section.heading, 'us').toLowerCase().replace(/[^a-z0-9]+/g, '-')}`} className="block rounded-lg -mx-2 px-2 py-2 transition hover:bg-surface hover:text-secondary">
                        {getLocalized(section.heading, language)}
                      </a>
                    </li>
                  ))}
                </ul>
              </div>

              <div className="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">
                  {t.newsRelatedRoutes}
                </p>
                <div className="mt-4 grid gap-3">
                  {relatedRoutes.map((route) => (
                    <Link
                      key={route.id}
                      to={`/routes/${route.id}`}
                      className="group rounded-xl border border-line bg-canvas p-4 transition hover:-translate-y-0.5 hover:border-secondary hover:bg-surface"
                    >
                      <p className="flex items-center gap-2 text-sm font-bold text-ink transition group-hover:text-secondary">
                        <RouteIcon className="h-4 w-4 text-secondary" />
                        {getLocalized(route.title, language)}
                      </p>
                      <p className="mt-2 text-xs font-semibold leading-5 text-muted">{getLocalized(route.bestFor, language)}</p>
                    </Link>
                  ))}
                </div>
                <Link
                  to="/routes"
                  className="mt-5 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-5 py-2.5 text-sm font-bold text-ink transition hover:-translate-y-0.5 hover:border-secondary hover:bg-subtle hover:text-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
                >
                  {t.viewAllRoutes}
                </Link>
              </div>
            </aside>
          </div>
        </div>
      </article>

      <NewsCardsSection
        articles={relatedArticles}
        language={language}
        eyebrow={t.newsRelatedArticles}
        title={t.newsReadMoreGuides}
        text={t.newsContinuePlanning}
        variant="compact"
      />
    </>
  );
}
