import { router } from '@inertiajs/react';
import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { CheckoutSteps } from '../components/checkout/CheckoutSteps';
import { SummaryCard } from '../components/checkout/SummaryCard';
import { DateField } from '../components/ui/DateField';
import { Dropdown } from '../components/ui/Dropdown';
import { Field } from '../components/ui/Field';
import { PageShell } from '../components/ui/PageShell';
import { Seo } from '../components/seo/Seo';
import { cardHoverClass, primaryButtonClass } from '../components/ui/styles';
import { formatCurrency } from '../utils/currency';
import { getLocalized, localizeDuration } from '../utils/localization';
import { useBooking } from '../context/BookingContext';

function getRoutePrice(route, currency = 'IDR') {
  return currency === 'USD' ? route.basePriceUsd : route.basePriceIdr ?? route.basePrice;
}

export function BookingPage() {
  const {
    t,
    language,
    booking,
    setBooking,
    selectedRoute,
    selectedRouteId,
    setSelectedRouteId,
    routes,
    bookingSummary,
    bookingBlock,
    dateAvailability,
    publicData,
    updateBooking,
  } = useBooking();
  const bookingOptions = publicData.bookingOptions ?? {};
  const paxOptions = bookingOptions.paxOptions ?? [];
  const travelerTypeOptions = bookingOptions.travelerTypeOptions ?? [];
  const currencyOptions = bookingOptions.currencyOptions ?? [];
  const localizedPaxOptions = paxOptions.map((option) => ({ ...option, label: getLocalized(option.label, language) }));
  const localizedTravelerTypeOptions = travelerTypeOptions.map((option) => ({
    ...option,
    label: getLocalized(option.label, language),
    meta: getLocalized(option.meta, language),
  }));
  const localizedCurrencyOptions = currencyOptions.map((option) => ({
    ...option,
    label: getLocalized(option.label, language),
    meta: getLocalized(option.meta, language),
  }));
  const availabilityByDate = selectedRoute?.availabilityByDate ?? {};
  const availabilityLabel = t[dateAvailability.status] ?? dateAvailability.status;

  function toggleAddOn(addOnId) {
    const selectedAddOns = booking.addOns.includes(addOnId)
      ? booking.addOns.filter((item) => item !== addOnId)
      : [...booking.addOns, addOnId];

    updateBooking({
      addOns: selectedAddOns,
    }, { recalculate: true });
  }

  function submitDraft(event) {
    event.preventDefault();

    router.post('/booking', {
      route: selectedRouteId,
      date: booking.date,
      pax: booking.pax,
      pickup: booking.pickup,
      traveler_type: booking.travelerType,
      currency: booking.currency,
      add_ons: booking.addOns,
      voucher: booking.voucher ?? 'BROMO10',
    });
  }

  return (
    <>
      <Seo
        title="Booking Request | Tinggal Jalan"
        description="Send a Tinggal Jalan tour booking request with route, date, guests, pickup point, currency, and add-ons."
        path="/booking"
        language={language}
        noindex
      />
      <PageShell eyebrow={t.bookingEyebrow} title={t.tripSetupTitle}>
      <div className="relative">
        <div className="relative">
          <CheckoutSteps current={0} />
        </div>
      </div>
      <div className="relative grid gap-8 lg:grid-cols-[1fr_0.85fr]">
        <form className={`rounded-2xl border border-brandLine bg-white p-5 shadow-soft sm:p-6 ${cardHoverClass}`} onSubmit={submitDraft}>
          <p className="mb-5 text-sm font-semibold leading-6 text-brandMuted">{t.tripSetupText}</p>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t.route}>
              <Dropdown
                value={selectedRouteId}
                options={routes.map((route) => ({
                  label: getLocalized(route.title, language),
                  value: route.id,
                  meta: `${localizeDuration(route.duration, language)} - ${formatCurrency(getRoutePrice(route, booking.currency), bookingSummary.currency)}${t.perPax}`,
                }))}
                onChange={setSelectedRouteId}
              />
            </Field>
            <Field label={t.travelerType}>
              <Dropdown
                value={booking.travelerType}
                options={localizedTravelerTypeOptions}
                onChange={(travelerType) => updateBooking({ travelerType }, { recalculate: true })}
              />
            </Field>
            <Field label={t.currency}>
              <Dropdown
                value={booking.currency}
                options={localizedCurrencyOptions}
                onChange={(currency) => updateBooking({ currency }, { recalculate: true })}
              />
            </Field>
            <Field label={t.date}>
              <DateField
                value={booking.date}
                language={language}
                availabilityByDate={availabilityByDate}
                showLegend
                onChange={(date) => updateBooking({ date }, { recalculate: true })}
              />
            </Field>
            <Field label={t.pax}>
              <Dropdown value={booking.pax} options={localizedPaxOptions} onChange={(pax) => updateBooking({ pax }, { recalculate: true })} />
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
          {selectedRoute.addOns?.length ? (
            <div className="mt-5">
              <p className="mb-3 text-sm font-semibold text-brandDark">{t.addOns}</p>
              <div className="grid gap-3 sm:grid-cols-2">
                {selectedRoute.addOns.map((addOn) => {
                  const checked = booking.addOns.includes(addOn.id);
                  const addOnPrice = booking.currency === 'USD' ? addOn.priceUsd : addOn.priceIdr;
                  const pricingLabel = addOn.pricing === 'perPax' ? t.perPax : t.perBooking;

                  return (
                    <label
                      key={addOn.id}
                      className={`cursor-pointer rounded-xl border p-4 transition ${
                        checked ? 'border-brandBlue bg-brandSoft' : 'border-brandLine bg-brandLight hover:border-brandBlue/40 hover:bg-white'
                      }`}
                    >
                      <span className="flex items-start gap-3">
                        <input
                          type="checkbox"
                          className="mt-1 h-4 w-4 accent-brandBlue"
                          checked={checked}
                          onChange={() => toggleAddOn(addOn.id)}
                        />
                        <span>
                          <span className="block text-sm font-bold text-brandDark">{getLocalized(addOn.title, language)}</span>
                          <span className="mt-1 block text-xs font-semibold leading-5 text-brandMuted">{getLocalized(addOn.description, language)}</span>
                          <span className="mt-2 block text-xs font-bold text-brandBlue">
                            {formatCurrency(addOnPrice, booking.currency)} {pricingLabel}
                          </span>
                        </span>
                      </span>
                    </label>
                  );
                })}
              </div>
            </div>
          ) : null}
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
                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-brandMuted/30 px-4 py-2 text-sm font-bold text-brandMuted sm:min-h-11 sm:px-5 sm:py-2.5"
                disabled
              >
                {dateAvailability.status === 'booked' ? t.booked : t.blockedTitle}
              </button>
            ) : (
              <button type="submit" className={primaryButtonClass}>
                {t.continueContact} <ArrowRight className="h-4 w-4" />
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
          language={language}
        />
      </div>
      </PageShell>
    </>
  );
}
