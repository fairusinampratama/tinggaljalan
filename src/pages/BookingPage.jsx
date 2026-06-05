import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { CheckoutSteps } from '../components/checkout/CheckoutSteps';
import { SummaryCard } from '../components/checkout/SummaryCard';
import { DateField } from '../components/ui/DateField';
import { Dropdown } from '../components/ui/Dropdown';
import { Field } from '../components/ui/Field';
import { PageShell } from '../components/ui/PageShell';
import { cardHoverClass, primaryButtonClass } from '../components/ui/styles';
import { paxOptions, pickupOptions, travelerTypeOptions } from '../data/bookingOptions';
import { routeArticles } from '../data/routes';
import { getRoutePrice } from '../utils/booking';
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
  } = useBooking();
  const localizedPickupOptions = pickupOptions.map((option) => ({ ...option, label: getLocalized(option.label, language) }));
  const localizedPaxOptions = paxOptions.map((option) => ({ ...option, label: getLocalized(option.label, language) }));
  const localizedTravelerTypeOptions = travelerTypeOptions.map((option) => ({
    ...option,
    label: getLocalized(option.label, language),
    meta: getLocalized(option.meta, language),
  }));

  return (
    <PageShell eyebrow={t.bookingEyebrow} title={t.tripSetupTitle}>
      <CheckoutSteps current={0} />
      <div className="grid gap-8 lg:grid-cols-[1fr_0.85fr]">
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
                onChange={(date) => setBooking((current) => ({ ...current, date }))}
              />
            </Field>
            <Field label={t.pax}>
              <Dropdown value={booking.pax} options={localizedPaxOptions} onChange={(pax) => setBooking((current) => ({ ...current, pax }))} />
            </Field>
            <Field label={t.pickup}>
              <Dropdown value={booking.pickup} options={localizedPickupOptions} onChange={(pickup) => setBooking((current) => ({ ...current, pickup }))} />
            </Field>
          </div>
          <div className="mt-6 flex justify-end">
            {bookingBlock.blocked ? (
              <button
                type="button"
                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-brandMuted/30 px-4 py-2 text-sm font-extrabold text-brandMuted sm:min-h-11 sm:px-5 sm:py-2.5"
                disabled
              >
                {t.blockedTitle}
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
          language={language}
        />
      </div>
    </PageShell>
  );
}
