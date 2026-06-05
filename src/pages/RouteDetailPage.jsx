import { Navigate, useNavigate, useParams } from 'react-router-dom';
import { FaqSection } from '../components/sections/FaqSection';
import { RouteDetailSection } from '../components/sections/RouteDetailSection';
import { getRouteById } from '../data/routes';
import { generalFaqItems } from '../data/faq';
import { useBooking } from '../context/BookingContext';

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

  return (
    <>
      <RouteDetailSection t={t} selectedArticle={route} whatsappUrl={whatsappUrl} onBookRoute={bookRoute} />
      <FaqSection items={generalFaqItems} language={language} />
    </>
  );
}
