import { usePage } from '@inertiajs/react';
import { Navigate, useNavigate, useParams } from 'react-router-dom';
import { FaqSection } from '../components/sections/FaqSection';
import { NewsCardsSection } from '../components/sections/NewsCardsSection';
import { RouteDetailSection } from '../components/sections/RouteDetailSection';
import { Seo } from '../components/seo/Seo';
import { useBooking } from '../context/BookingContext';
import { buildRouteJsonLd, getRouteSeo } from '../utils/seo';

export function RouteDetailPage() {
  const { props } = usePage();
  const { routeId } = useParams();
  const navigate = useNavigate();
  const { language, t, whatsappUrl, publicData } = useBooking();
  const route = props.route ?? publicData.routes?.find((item) => item.id === routeId || item.slug === routeId);

  if (!route) {
    return <Navigate to="/routes" replace />;
  }

  const bookRoute = () => {
    navigate(`/booking?route=${route.id}`);
  };
  const seo = props.seo ?? getRouteSeo(route, language);
  const relatedArticles = props.relatedArticles ?? [];
  const faqItems = props.faqs ?? publicData.faqs ?? [];

  return (
    <>
      <Seo {...seo} jsonLd={seo.json_ld ?? buildRouteJsonLd(route, language)} language={language} />
      <RouteDetailSection t={t} selectedArticle={route} whatsappUrl={whatsappUrl} onBookRoute={bookRoute} />
      <NewsCardsSection
        articles={relatedArticles}
        language={language}
        eyebrow={language === 'id' ? 'Berita & Panduan' : 'News & Guides'}
        title={language === 'id' ? 'Artikel terkait rute ini' : 'Articles related to this route'}
        text={
          language === 'id'
            ? 'Baca tips dan panduan sebelum menentukan jadwal perjalanan.'
            : 'Read tips and guides before choosing your travel date.'
        }
        variant="compact"
      />
      <FaqSection items={faqItems} language={language} />
    </>
  );
}
