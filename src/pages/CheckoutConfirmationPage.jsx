import { Link } from 'react-router-dom';
import { CheckCircle, MessageCircle } from 'lucide-react';
import { CheckoutSteps } from '../components/checkout/CheckoutSteps';
import { SummaryCard } from '../components/checkout/SummaryCard';
import { PageShell } from '../components/ui/PageShell';
import { Seo } from '../components/seo/Seo';
import { cardHoverClass, iconSize, secondaryButtonClass, whatsappButtonClass } from '../components/ui/styles';
import { useBooking } from '../context/BookingContext';

export function CheckoutConfirmationPage() {
  const { t, language, booking, selectedRoute, bookingSummary, bookingBlock, dateAvailability, bookingCode, whatsappUrl } = useBooking();

  return (
    <>
      <Seo
        title="Booking Confirmation | Tinggal Jalan"
        description="Tinggal Jalan booking request confirmation and WhatsApp handoff."
        path="/checkout/confirmation"
        language={language}
        noindex
      />
      <PageShell eyebrow="Booking" title={t.requestSentTitle}>
      <CheckoutSteps current={2} />
      <div className="grid gap-8 lg:grid-cols-[1fr_0.85fr]">
        <section className={`rounded-2xl border border-brandLine bg-white p-5 shadow-soft sm:p-6 ${cardHoverClass}`}>
          <div className="grid h-11 w-11 place-items-center rounded-full bg-brandBlue text-white">
            <CheckCircle className="h-5 w-5" />
          </div>
          <h2 className="mt-5 text-2xl font-bold">{t.waitingConfirmation}</h2>
          <p className="mt-3 text-sm font-semibold leading-6 text-brandMuted">
            {t.requestSentText}
          </p>
          <div className={`mt-5 rounded-2xl border border-transparent bg-brandLight p-4 ${cardHoverClass}`}>
            <p className="text-sm font-bold text-brandDark">{t.bookingCode}</p>
            <p className="mt-1 text-2xl font-extrabold text-brandBlue">{bookingCode}</p>
            <p className="mt-3 text-sm font-semibold leading-6 text-brandMuted">
              {t.paymentAfterConfirmation}: {bookingSummary.paymentGateway}. {t.paymentDeferredNote}
            </p>
          </div>
          <div className="mt-6 flex flex-wrap gap-3">
            <a href={whatsappUrl} target="_blank" rel="noreferrer" className={whatsappButtonClass}>
              <MessageCircle className={iconSize} /> {t.sendToWhatsapp}
            </a>
            <Link to="/" className={secondaryButtonClass}>
              {t.done}
            </Link>
          </div>
        </section>
        <SummaryCard
          t={t}
          booking={booking}
          selectedRoute={selectedRoute}
          summary={bookingSummary}
          bookingBlock={bookingBlock}
          dateAvailability={dateAvailability}
          showBlock={false}
          language={language}
        />
      </div>
      </PageShell>
    </>
  );
}
