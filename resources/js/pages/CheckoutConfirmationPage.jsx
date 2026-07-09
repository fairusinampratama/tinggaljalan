import { usePage } from '@inertiajs/react';
import { Link } from 'react-router-dom';
import { CheckCircle, MessageCircle, Info } from 'lucide-react';
import { CheckoutSteps } from '../components/checkout/CheckoutSteps';
import { SummaryCard } from '../components/checkout/SummaryCard';
import { PageShell } from '../components/ui/PageShell';
import { Seo } from '../components/seo/Seo';
import { cardHoverClass, iconSize, secondaryButtonClass, whatsappButtonClass } from '../components/ui/styles';
import { useBooking } from '../context/BookingContext';

export function CheckoutConfirmationPage() {
  const { props } = usePage();
  const { t, language, booking, selectedRoute, bookingSummary, bookingBlock, dateAvailability, bookingCode, whatsappUrl } = useBooking();
  const savedBookingCode = props.savedBooking?.code ?? bookingCode;
  const handoffUrl = props.whatsappUrl ?? whatsappUrl;

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
        <section>
          <h2 className="text-3xl font-bold text-ink">{t.waitingConfirmation}</h2>
          <p className="mt-3 text-sm leading-relaxed text-muted">
            {t.requestSentText}
          </p>
          <div className="mt-6 rounded-2xl border border-line bg-surface p-5 sm:p-6">
            <p className="text-sm font-bold text-ink">{t.bookingCode}</p>
            <p className="mt-1 text-2xl font-bold text-secondary sm:text-3xl">{savedBookingCode}</p>
            <div className="mt-6 text-sm leading-relaxed text-muted">
              <p>
                {t.paymentAfterConfirmation}: {bookingSummary.paymentGateway}. {bookingSummary.paymentNote || t.paymentDeferredNote}
              </p>
              {bookingSummary.currency === 'USD' && bookingSummary.usdPaymentNote ? (
                <p className="mt-4">{bookingSummary.usdPaymentNote}</p>
              ) : null}
            </div>
          </div>
          <div className="mt-8 flex flex-wrap items-center gap-4">
            <a href={handoffUrl} target="_blank" rel="noreferrer" className={`${whatsappButtonClass} w-full justify-center sm:w-auto`}>
              <MessageCircle className={iconSize} /> {t.sendToWhatsapp}
            </a>
            <Link to="/" className={`${secondaryButtonClass} w-full justify-center sm:w-auto`}>
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
