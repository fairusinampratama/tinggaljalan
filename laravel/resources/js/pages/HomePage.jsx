import { usePage } from '@inertiajs/react';
import { AvailableOnSection } from '../components/sections/AvailableOnSection';
import { DestinationSection } from '../components/sections/DestinationSection';
import { FaqSection } from '../components/sections/FaqSection';
import { Hero } from '../components/sections/Hero';
import { HomeCtaSection } from '../components/sections/HomeCtaSection';
import { NewsCardsSection } from '../components/sections/NewsCardsSection';
import { ReviewsSection } from '../components/sections/ReviewsSection';
import { RouteArticlesSection } from '../components/sections/RouteArticlesSection';
import { TrustStripSection } from '../components/sections/TrustStripSection';
import { WhyChooseSection } from '../components/sections/WhyChooseSection';
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
      <Hero
        t={t}
        language={language}
        booking={booking}
        setBooking={setBooking}
        whatsappUrl={whatsappUrl}
      />
      <TrustStripSection items={publicData.trustStats} />
      <DestinationSection />
      <RouteArticlesSection
        t={t}
        routes={featuredRouteItems}
        setSelectedRouteId={setSelectedRouteId}
        whatsappUrl={whatsappUrl}
        showViewAll
      />
      <NewsCardsSection
        articles={latestArticles}
        language={language}
        eyebrow={language === 'id' ? 'Berita & Panduan' : 'News & Guides'}
        title={language === 'id' ? 'Panduan terbaru untuk rencana perjalananmu' : 'Latest guides for your travel planning'}
        text={
          language === 'id'
            ? 'Baca tips destinasi, itinerary, dan update rute sebelum memilih paket.'
            : 'Read destination tips, itineraries, and route updates before choosing a package.'
        }
        showViewAll
        variant="compact"
      />
      <WhyChooseSection items={publicData.home?.whyChooseItems} />
      <ReviewsSection items={publicData.reviews} />
      <AvailableOnSection items={publicData.platformLinks} />
      <FaqSection items={faqItems} language={language} />
      <HomeCtaSection whatsappUrl={whatsappUrl} />
    </>
  );
}
