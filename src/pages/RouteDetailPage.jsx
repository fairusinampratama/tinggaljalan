import { Navigate, useNavigate, useParams } from 'react-router-dom';
import { FaqSection } from '../components/sections/FaqSection';
import { RouteDetailSection } from '../components/sections/RouteDetailSection';
import { Seo } from '../components/seo/Seo';
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

  return (
    <>
      <Seo {...seo} jsonLd={buildRouteJsonLd(route, language)} language={language} />
      <RouteDetailSection t={t} selectedArticle={route} whatsappUrl={whatsappUrl} onBookRoute={bookRoute} />
      <FaqSection items={generalFaqItems} language={language} />
    </>
  );
}
