import { router, usePage } from '@inertiajs/react';
import { Link } from 'react-router-dom';
import { Send, Ticket } from 'lucide-react';
import { isPossiblePhoneNumber } from 'react-phone-number-input';
import { CheckoutSteps } from '../components/checkout/CheckoutSteps';
import { PhoneInput } from '../components/checkout/PhoneInput';
import { SummaryCard } from '../components/checkout/SummaryCard';
import { Field } from '../components/ui/Field';
import { PageShell } from '../components/ui/PageShell';
import { Seo } from '../components/seo/Seo';
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
    recalculateBooking,
    bookingSummary,
    bookingBlock,
    dateAvailability,
    whatsappUrl,
  } = useBooking();
  const { errors = {} } = usePage().props;
  const whatsappValid = booking.whatsapp ? isPossiblePhoneNumber(booking.whatsapp) : false;
  const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(booking.email.trim());
  const contactComplete = booking.name.trim() && whatsappValid && emailValid;
  const dateUnavailable = bookingBlock.blocked || dateAvailability.status === 'booked';
  const quoteRequired = Boolean(bookingSummary.quoteRequired);

  function submitBooking(event) {
    event.preventDefault();

    if (quoteRequired) {
      return;
    }

    router.post('/checkout/review', {
      name: booking.name,
      whatsapp: booking.whatsapp,
      whatsapp_country: booking.whatsappCountry,
      email: booking.email,
      voucher: voucherCode,
      notes: booking.notes,
    });
  }

  return (
    <>
      <Seo
        title="Traveler Contact | Tinggal Jalan"
        description="Complete traveler contact details for a Tinggal Jalan booking request."
        path="/checkout/review"
        language={language}
        noindex
      />
      <PageShell eyebrow="Booking" title={t.contactTitle}>
      <CheckoutSteps current={1} />
      <div className="grid gap-8 lg:grid-cols-[1fr_0.85fr]">
        <form className={`rounded-xl border border-line bg-surface p-5 shadow-soft sm:p-6 ${cardHoverClass}`} onSubmit={submitBooking}>
          <h2 className="text-2xl font-bold">{t.contactDetails}</h2>
          <p className="mt-2 text-sm font-semibold leading-6 text-muted">{t.contactText}</p>
          <div className="mt-5 grid gap-4 sm:grid-cols-2">
            <Field label={`${t.fullName} *`}>
              <input
                required
                autoComplete="name"
                className="w-full rounded-xl border border-line bg-canvas px-4 py-3 text-sm font-bold outline-none transition hover:border-secondary/40 hover:bg-surface focus:border-secondary"
                value={booking.name}
                onChange={(event) => setBooking((current) => ({ ...current, name: event.target.value }))}
                placeholder={t.namePlaceholder}
              />
              {errors.name ? <p className="mt-1 text-xs font-bold text-red-600">{errors.name}</p> : null}
            </Field>
            <Field label={`${t.whatsapp} *`}>
              <PhoneInput
                value={booking.whatsapp}
                country={booking.whatsappCountry}
                required
                invalid={Boolean(errors.whatsapp || (booking.whatsapp && !whatsappValid))}
                onChange={(whatsapp) => setBooking((current) => ({ ...current, whatsapp }))}
                onCountryChange={(whatsappCountry) => setBooking((current) => ({ ...current, whatsappCountry }))}
              />
              <p className="mt-1.5 text-xs font-semibold text-muted">{t.whatsappHelp}</p>
              {errors.whatsapp ? <p className="mt-1 text-xs font-bold text-red-600">{errors.whatsapp}</p> : null}
              {!errors.whatsapp && booking.whatsapp && !whatsappValid ? <p className="mt-1 text-xs font-bold text-red-600">{t.whatsappInvalid}</p> : null}
            </Field>
            <Field label={`${t.email} *`}>
              <input
                type="email"
                required
                autoComplete="email"
                aria-invalid={Boolean(errors.email || (booking.email && !emailValid))}
                className={`w-full rounded-xl border bg-canvas px-4 py-3 text-sm font-bold outline-none transition hover:bg-surface ${
                  errors.email || (booking.email && !emailValid)
                    ? 'border-red-400 focus:border-red-500'
                    : 'border-line hover:border-secondary/40 focus:border-secondary'
                }`}
                value={booking.email}
                onChange={(event) => setBooking((current) => ({ ...current, email: event.target.value }))}
                placeholder="name@example.com"
              />
              <p className="mt-1.5 text-xs font-semibold text-muted">{t.emailHelp}</p>
              {errors.email ? <p className="mt-1 text-xs font-bold text-red-600">{errors.email}</p> : null}
            </Field>
            <Field label={t.voucher}>
              <div className="flex gap-2">
                <input
                  className="min-w-0 flex-1 rounded-xl border border-line bg-canvas px-4 py-3 text-sm font-bold uppercase outline-none transition hover:border-secondary/40 hover:bg-surface focus:border-secondary"
                  value={voucherCode}
                  onChange={(event) => setVoucherCode(event.target.value)}
                  placeholder={t.voucherPlaceholder}
                />
                <button
                  type="button"
                  className="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-secondary px-4 text-white transition duration-200 hover:bg-primary hover:text-white hover:shadow-lg hover:shadow-secondary/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
                  onClick={() => {
                    setAppliedVoucher(voucherCode);
                    recalculateBooking({ ...booking, voucher: voucherCode });
                  }}
                  aria-label={t.applyVoucher}
                >
                  <Ticket className="h-4 w-4" />
                  <span className="text-sm font-semibold">{t.applyVoucher}</span>
                </button>
              </div>
              <p className="mt-1.5 text-xs font-semibold text-muted">{t.voucherHelp}</p>
            </Field>
            <div className="sm:col-span-2">
              <Field label={t.notes}>
                <textarea
                  className="min-h-28 w-full resize-y rounded-xl border border-line bg-canvas px-4 py-3 text-sm font-bold outline-none transition hover:border-secondary/40 hover:bg-surface focus:border-secondary"
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
            {quoteRequired || dateUnavailable || !contactComplete ? (
              <button
                type="button"
                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-muted/30 px-4 py-2 text-sm font-bold text-muted sm:min-h-11 sm:px-5 sm:py-2.5"
                disabled
              >
                {quoteRequired ? t.customGroupContactFirst : dateUnavailable ? (dateAvailability.status === 'booked' ? t.booked : t.blockedTitle) : t.completeContact}
              </button>
            ) : (
              <button type="submit" className={primaryButtonClass}>
                <Send className="h-4 w-4" /> {t.sendBookingRequest}
              </button>
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
          whatsappUrl={whatsappUrl}
          language={language}
        />
      </div>
      </PageShell>
    </>
  );
}