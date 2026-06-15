import { Navigate, useNavigate, useParams } from 'react-router-dom';
import { FaqSection } from '../components/sections/FaqSection';
import { NewsCardsSection } from '../components/sections/NewsCardsSection';
import { RouteDetailSection } from '../components/sections/RouteDetailSection';
import { Seo } from '../components/seo/Seo';
import { getRelatedNewsArticles } from '../data/news';
import { getRouteById } from '../data/routes';
import { generalFaqItems } from '../data/faq';
import { useBooking } from '../context/BookingContext';
import { buildRouteJsonLd, getRouteSeo } from '../utils/seo';

export function RouteDetailPage() {
  const { routeId } = useParams();
  const navigate = useNavigate();
  const { language, t, setSelectedRouteId, whatsappUrl } = useBooking();
  const route = getRouteById(routeId);

  if (!route) {
    return <Navigate to="/routes" replace />;
  }

  const bookRoute = () => {
    setSelectedRouteId(route.id);
    navigate('/booking');
  };
  const seo = getRouteSeo(route, language);
  const relatedArticles = getRelatedNewsArticles({ routeId: route.id, destinationId: route.destinationId, limit: 3 });

  return (
    <>
      <Seo {...seo} jsonLd={buildRouteJsonLd(route, language)} language={language} />
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
      <FaqSection items={generalFaqItems} language={language} />
    </>
  );
}
