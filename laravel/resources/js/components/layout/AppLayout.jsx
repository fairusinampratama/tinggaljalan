import { usePage } from '@inertiajs/react';
import { FloatingWhatsAppButton } from '../sections/FloatingWhatsAppButton';
import { Footer } from '../sections/Footer';
import { Navbar } from '../sections/Navbar';
import { useBooking } from '../../context/BookingContext';

export function AppLayout({ children }) {
  const { language, setLanguage, t, whatsappUrl } = useBooking();
  const { url } = usePage();
  const pathname = url.split('?')[0];
  const isRouteDetailPage = pathname.startsWith('/routes/');

  return (
    <main className="min-h-screen bg-brandLight text-brandDark">
      <Navbar language={language} setLanguage={setLanguage} t={t} />
      {children}
      <Footer t={t} whatsappUrl={whatsappUrl} />
      <FloatingWhatsAppButton whatsappUrl={whatsappUrl} label={t.chat} avoidMobileBottomBar={isRouteDetailPage} />
    </main>
  );
}
