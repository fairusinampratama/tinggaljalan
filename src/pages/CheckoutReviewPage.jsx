import { Link } from 'react-router-dom';
import { Send, Ticket } from 'lucide-react';
import { CheckoutSteps } from '../components/checkout/CheckoutSteps';
import { SummaryCard } from '../components/checkout/SummaryCard';
import { Field } from '../components/ui/Field';
import { PageShell } from '../components/ui/PageShell';
import { cardHoverClass, primaryButtonClass, secondaryButtonClass } from '../components/ui/styles';
import { useBooking } from '../context/BookingContext';

export function CheckoutReviewPage() {
  const {
    t,
    language,
    booking,
    setBooking,
    selectedRoute,
    voucherCode,
    setVoucherCode,
    setAppliedVoucher,
    bookingSummary,
    bookingBlock,
    dateAvailability,
  } = useBooking();
  const contactComplete = booking.name.trim() && booking.whatsapp.trim();
  const dateUnavailable = bookingBlock.blocked || dateAvailability.status === 'booked';

  return (
    <PageShell eyebrow="Booking" title={t.contactTitle}>
      <CheckoutSteps current={1} />
      <div className="grid gap-8 lg:grid-cols-[1fr_0.85fr]">
        <form className={`rounded-2xl border border-brandLine bg-white p-5 shadow-soft sm:p-6 ${cardHoverClass}`} onSubmit={(event) => event.preventDefault()}>
          <h2 className="text-2xl font-extrabold">{t.contactDetails}</h2>
          <p className="mt-2 text-sm font-semibold leading-6 text-brandMuted">{t.contactText}</p>
          <div className="mt-5 grid gap-4 sm:grid-cols-2">
            <Field label={t.fullName}>
              <input
                className="w-full rounded-xl border border-brandLine bg-brandLight px-4 py-3 text-sm font-bold outline-none transition hover:border-brandBlue/40 hover:bg-white focus:border-brandBlue"
                value={booking.name}
                onChange={(event) => setBooking((current) => ({ ...current, name: event.target.value }))}
                placeholder={t.namePlaceholder}
              />
            </Field>
            <Field label={t.whatsapp}>
              <input
                className="w-full rounded-xl border border-brandLine bg-brandLight px-4 py-3 text-sm font-bold outline-none transition hover:border-brandBlue/40 hover:bg-white focus:border-brandBlue"
                value={booking.whatsapp}
                onChange={(event) => setBooking((current) => ({ ...current, whatsapp: event.target.value }))}
                placeholder={t.whatsappPlaceholder}
              />
            </Field>
            <Field label={t.emailOptional}>
              <input
                className="w-full rounded-xl border border-brandLine bg-brandLight px-4 py-3 text-sm font-bold outline-none transition hover:border-brandBlue/40 hover:bg-white focus:border-brandBlue"
                value={booking.email}
                onChange={(event) => setBooking((current) => ({ ...current, email: event.target.value }))}
                placeholder={t.optionalPlaceholder}
              />
            </Field>
            <Field label={t.voucher}>
              <div className="flex gap-2">
                <input
                  className="min-w-0 flex-1 rounded-xl border border-brandLine bg-brandLight px-4 py-3 text-sm font-black uppercase outline-none transition hover:border-brandBlue/40 hover:bg-white focus:border-brandBlue"
                  value={voucherCode}
                  onChange={(event) => setVoucherCode(event.target.value)}
                />
                <button
                  type="button"
                  className="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brandBlue text-white transition duration-200 hover:-translate-y-0.5 hover:bg-brandDark hover:shadow-lg hover:shadow-brandBlue/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
                  onClick={() => setAppliedVoucher(voucherCode)}
                  aria-label={t.voucher}
                >
                  <Ticket className="h-4 w-4" />
                </button>
              </div>
            </Field>
            <div className="sm:col-span-2">
              <Field label={t.notes}>
                <textarea
                  className="min-h-28 w-full resize-y rounded-xl border border-brandLine bg-brandLight px-4 py-3 text-sm font-bold outline-none transition hover:border-brandBlue/40 hover:bg-white focus:border-brandBlue"
                  value={booking.notes}
                  onChange={(event) => setBooking((current) => ({ ...current, notes: event.target.value }))}
                  placeholder={t.notesPlaceholder}
                />
              </Field>
            </div>
          </div>
          <div className="mt-6 flex flex-wrap gap-3">
            <Link to="/booking" className={secondaryButtonClass}>
              {t.editTrip}
            </Link>
            {dateUnavailable || !contactComplete ? (
              <button
                type="button"
                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-brandMuted/30 px-4 py-2 text-sm font-extrabold text-brandMuted sm:min-h-11 sm:px-5 sm:py-2.5"
                disabled
              >
                {dateUnavailable ? (dateAvailability.status === 'booked' ? t.booked : t.blockedTitle) : t.completeContact}
              </button>
            ) : (
              <Link to="/checkout/confirmation" className={primaryButtonClass}>
                <Send className="h-4 w-4" /> {t.sendBookingRequest}
              </Link>
            )}
          </div>
        </form>
        <SummaryCard
          t={t}
          booking={booking}
          selectedRoute={selectedRoute}
          summary={bookingSummary}
          bookingBlock={bookingBlock}
          dateAvailability={dateAvailability}
          language={language}
        />
      </div>
    </PageShell>
  );
}
