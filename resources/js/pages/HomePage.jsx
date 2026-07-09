import { usePage } from '@inertiajs/react';
import { DestinationSection } from '../components/sections/DestinationSection';
import { FaqSection } from '../components/sections/FaqSection';
import { Hero } from '../components/sections/Hero';
import { HomeCtaSection } from '../components/sections/HomeCtaSection';
import { NewsCardsSection } from '../components/sections/NewsCardsSection';
import { RouteArticlesSection } from '../components/sections/RouteArticlesSection';
import { SocialProofSection } from '../components/sections/SocialProofSection';
import { TrustStripSection } from '../components/sections/TrustStripSection';
import { Seo } from '../components/seo/Seo';
import { useBooking } from '../context/BookingContext';
import { defaultSeo } from '../utils/seo';

export function HomePage() {
  const { props } = usePage();
  const { language, t, booking, setBooking, setSelectedRouteId, whatsappUrl, publicData } = useBooking();
  const latestArticles = props.latestArticles ?? publicData.articles?.slice(0, 3) ?? [];
  const featuredRouteItems = props.featuredRoutes ?? publicData.routes?.filter((route) => route.featured).slice(0, 6) ?? [];
  const faqItems = props.faqs ?? publicData.faqs ?? [];

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
      </div>
    </>
  );
}
