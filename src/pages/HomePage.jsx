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
import { generalFaqItems } from '../data/faq';
import { getLatestNewsArticles } from '../data/news';
import { featuredRoutes } from '../data/routes';
import { defaultSeo } from '../utils/seo';

export function HomePage() {
  const { language, t, booking, setBooking, setSelectedRouteId, whatsappUrl } = useBooking();
  const latestArticles = getLatestNewsArticles(3);

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
      <TrustStripSection />
      <DestinationSection />
      <RouteArticlesSection
        t={t}
        routes={featuredRoutes}
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
      <WhyChooseSection />
      <ReviewsSection />
      <AvailableOnSection />
      <FaqSection items={generalFaqItems} language={language} />
      <HomeCtaSection whatsappUrl={whatsappUrl} />
    </>
  );
}
