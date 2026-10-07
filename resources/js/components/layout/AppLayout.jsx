import { usePage } from '@inertiajs/react';
import { FloatingWhatsAppButton } from '../sections/FloatingWhatsAppButton';
import { ConsentBanner } from '../sections/ConsentBanner';
import { Footer } from '../sections/Footer';
import { Navbar } from '../sections/Navbar';
import { useBooking } from '../../context/BookingContext';
import { DecorativeBackdrop } from '../decorative/DecorativeBackdrop';

export function AppLayout({ children }) {
  const { language, setLanguage, t, whatsappUrl } = useBooking();
  const { url } = usePage();
  const pathname = url.split('?')[0];
  const isRouteDetailPage = pathname.startsWith('/routes/');
  const backdrop = /^\/(booking|checkout)(\/|$)/.test(pathname)
    ? 'transaction'
    : isRouteDetailPage ? 'detail' : 'public';

  return (
    <main className="min-h-screen w-full min-w-0 overflow-x-clip bg-canvas text-ink">
      <Navbar language={language} setLanguage={setLanguage} t={t} />
      {pathname === '/' ? children : <DecorativeBackdrop key={pathname} variant={backdrop}>{children}</DecorativeBackdrop>}
      <Footer t={t} whatsappUrl={whatsappUrl} />
      <FloatingWhatsAppButton whatsappUrl={whatsappUrl} label={t.chat} avoidMobileBottomBar={isRouteDetailPage} />
      <ConsentBanner t={t} />
    </main>
  );
}
