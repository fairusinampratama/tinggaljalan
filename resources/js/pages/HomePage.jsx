import { lazy, Suspense } from 'react';
import { usePage } from '@inertiajs/react';
import { DestinationSection } from '../components/sections/DestinationSection';
import { Hero } from '../components/sections/Hero';
import { RouteArticlesSection } from '../components/sections/RouteArticlesSection';
import { TrustStripSection } from '../components/sections/TrustStripSection';
import { Seo } from '../components/seo/Seo';
import { useBooking } from '../context/BookingContext';
import { defaultSeo } from '../utils/seo';

const FaqSection = lazy(() => import('../components/sections/FaqSection').then((module) => ({ default: module.FaqSection })));
const HomeCtaSection = lazy(() => import('../components/sections/HomeCtaSection').then((module) => ({ default: module.HomeCtaSection })));
const NewsCardsSection = lazy(() => import('../components/sections/NewsCardsSection').then((module) => ({ default: module.NewsCardsSection })));
const SocialProofSection = lazy(() => import('../components/sections/SocialProofSection').then((module) => ({ default: module.SocialProofSection })));

export function HomePage() {
  const { props } = usePage();
  const { language, t, booking, setBooking, setSelectedRouteId, whatsappUrl, publicData } = useBooking();
  const latestArticles = props.latestArticles ?? [];
  const featuredRouteItems = props.featuredRoutes ?? [];
  const faqItems = props.faqs ?? [];

  return (
    <>
      <Seo {...defaultSeo} language={language} />
      <div className="home-page">
        <Hero
          t={t}
          language={language}
          booking={booking}
          setBooking={setBooking}
          whatsappUrl={whatsappUrl}
        />
        <TrustStripSection items={publicData.trustStats} />
        <DestinationSection items={props.destinations} />
        <RouteArticlesSection
          t={t}
          routes={featuredRouteItems}
          setSelectedRouteId={setSelectedRouteId}
          whatsappUrl={whatsappUrl}
          showViewAll
        />
        <Suspense fallback={null}>
          <SocialProofSection
            benefits={publicData.home?.whyChooseItems}
            reviews={props.reviews ?? publicData.reviews}
          />
          <NewsCardsSection
            articles={latestArticles}
            language={language}
            eyebrow={t.guidesEyebrow}
            title={t.guidesTitle}
            text={t.guidesText}
            showViewAll
            variant="compact"
          />
          <FaqSection items={faqItems} language={language} title={t.faqTitle} />
          <HomeCtaSection whatsappUrl={whatsappUrl} items={publicData.platformLinks} />
        </Suspense>
      </div>
    </>
  );
}