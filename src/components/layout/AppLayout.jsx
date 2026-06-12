import { Outlet, useLocation } from 'react-router-dom';
import { FloatingWhatsAppButton } from '../sections/FloatingWhatsAppButton';
import { Footer } from '../sections/Footer';
import { Navbar } from '../sections/Navbar';
import { useBooking } from '../../context/BookingContext';

export function AppLayout() {
  const { language, setLanguage, t, whatsappUrl } = useBooking();
  const { pathname } = useLocation();
  const isRouteDetailPage = pathname.startsWith('/routes/');

  return (
    <main className="min-h-screen bg-brandLight text-brandDark">
      <Navbar language={language} setLanguage={setLanguage} t={t} />
      <Outlet />
      <Footer t={t} whatsappUrl={whatsappUrl} />
      <FloatingWhatsAppButton whatsappUrl={whatsappUrl} label={t.chat} avoidMobileBottomBar={isRouteDetailPage} />
    </main>
  );
}
