import { AvailableOnSection } from '../components/sections/AvailableOnSection';
import { DestinationSection } from '../components/sections/DestinationSection';
import { FaqSection } from '../components/sections/FaqSection';
import { Hero } from '../components/sections/Hero';
import { HomeCtaSection } from '../components/sections/HomeCtaSection';
import { ReviewsSection } from '../components/sections/ReviewsSection';
import { RouteArticlesSection } from '../components/sections/RouteArticlesSection';
import { TrustStripSection } from '../components/sections/TrustStripSection';
import { WhyChooseSection } from '../components/sections/WhyChooseSection';
import { Seo } from '../components/seo/Seo';
import { useBooking } from '../context/BookingContext';
import { generalFaqItems } from '../data/faq';
import { featuredRoutes } from '../data/routes';
import { defaultSeo } from '../utils/seo';

export function HomePage() {
  const { language, t, booking, setBooking, setSelectedRouteId, whatsappUrl } = useBooking();

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
      <WhyChooseSection />
      <ReviewsSection />
      <AvailableOnSection />
      <FaqSection items={generalFaqItems} language={language} />
      <HomeCtaSection whatsappUrl={whatsappUrl} />
    </>
  );
}
