import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { CheckoutSteps } from '../components/checkout/CheckoutSteps';
import { SummaryCard } from '../components/checkout/SummaryCard';
import { DateField } from '../components/ui/DateField';
import { Dropdown } from '../components/ui/Dropdown';
import { Field } from '../components/ui/Field';
import { PageShell } from '../components/ui/PageShell';
import { cardHoverClass, primaryButtonClass } from '../components/ui/styles';
import { paxOptions, travelerTypeOptions } from '../data/bookingOptions';
import { routeArticles } from '../data/routes';
import { getAvailabilityByDate, getRoutePrice } from '../utils/booking';
import { formatCurrency } from '../utils/currency';
import { getLocalized, localizeDuration } from '../utils/localization';
import { useBooking } from '../context/BookingContext';

export function BookingPage() {
  const {
    t,
    language,
    booking,
    setBooking,
    selectedRoute,
    selectedRouteId,
    setSelectedRouteId,
    bookingSummary,
    bookingBlock,
    dateAvailability,
  } = useBooking();
  const localizedPaxOptions = paxOptions.map((option) => ({ ...option, label: getLocalized(option.label, language) }));
  const localizedTravelerTypeOptions = travelerTypeOptions.map((option) => ({
    ...option,
    label: getLocalized(option.label, language),
    meta: getLocalized(option.meta, language),
  }));
  const availabilityByDate = getAvailabilityByDate(selectedRoute);
  const availabilityLabel = t[dateAvailability.status] ?? dateAvailability.status;

  return (
    <PageShell eyebrow={t.bookingEyebrow} title={t.tripSetupTitle}>
      <div className="relative">
        <div className="adventure-path -right-12 top-2 hidden opacity-60 lg:block" />
        <div className="terrain-sweep left-4 top-16 hidden h-16 w-72 opacity-70 sm:block" />
        <div className="relative">
          <CheckoutSteps current={0} />
        </div>
      </div>
      <div className="relative grid gap-8 lg:grid-cols-[1fr_0.85fr]">
        <form className={`rounded-2xl border border-brandLine bg-white p-5 shadow-soft sm:p-6 ${cardHoverClass}`} onSubmit={(event) => event.preventDefault()}>
          <p className="mb-5 text-sm font-semibold leading-6 text-brandMuted">{t.tripSetupText}</p>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t.route}>
              <Dropdown
                value={selectedRouteId}
                options={routeArticles.map((route) => ({
                  label: getLocalized(route.title, language),
                  value: route.id,
                  meta: `${localizeDuration(route.duration, language)} - ${formatCurrency(getRoutePrice(route, booking.travelerType), bookingSummary.currency)}${t.perPax}`,
                }))}
                onChange={setSelectedRouteId}
              />
            </Field>
            <Field label={t.travelerType}>
              <Dropdown
                value={booking.travelerType}
                options={localizedTravelerTypeOptions}
                onChange={(travelerType) => setBooking((current) => ({ ...current, travelerType }))}
              />
            </Field>
            <Field label={t.date}>
              <DateField
                value={booking.date}
                language={language}
                availabilityByDate={availabilityByDate}
                showLegend
                onChange={(date) => setBooking((current) => ({ ...current, date }))}
              />
            </Field>
            <Field label={t.pax}>
              <Dropdown value={booking.pax} options={localizedPaxOptions} onChange={(pax) => setBooking((current) => ({ ...current, pax }))} />
            </Field>
            <Field label={t.pickup}>
              <input
                type="text"
                value={booking.pickup}
                placeholder={t.pickupPlaceholder}
                className="w-full rounded-xl border border-brandLine bg-brandLight px-4 py-3 text-sm font-bold outline-none transition hover:border-brandBlue/40 hover:bg-white focus:border-brandBlue"
                onChange={(event) => setBooking((current) => ({ ...current, pickup: event.target.value }))}
              />
            </Field>
          </div>
          <div className={`mt-4 rounded-xl border px-4 py-3 text-xs font-bold leading-5 ${
            dateAvailability.status === 'limited'
              ? 'border-amber-200 bg-amber-50 text-amber-700'
              : dateAvailability.status === 'booked' || dateAvailability.status === 'blocked'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-brandBlue/15 bg-brandSoft text-brandBlue'
          }`}>
            {t.availability}: {availabilityLabel}
            {dateAvailability.seatsLeft ? ` · ${dateAvailability.seatsLeft} seats left` : ''}
            {dateAvailability.reason ? ` · ${dateAvailability.reason}` : ''}
          </div>
          <div className="mt-6 flex justify-end">
            {bookingBlock.blocked || dateAvailability.status === 'booked' ? (
              <button
                type="button"
                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-brandMuted/30 px-4 py-2 text-sm font-extrabold text-brandMuted sm:min-h-11 sm:px-5 sm:py-2.5"
                disabled
              >
                {dateAvailability.status === 'booked' ? t.booked : t.blockedTitle}
              </button>
            ) : (
              <Link to="/checkout/review" className={primaryButtonClass}>
                {t.continueContact} <ArrowRight className="h-4 w-4" />
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
