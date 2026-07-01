import { createContext, useContext, useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { copy } from '../data/translations';
import { getRegionConfig, normalizeRegion } from '../utils/localization';

const BookingContext = createContext(null);

function normalizeDraft(draft = {}, defaults = {}) {
  return {
    name: '',
    email: '',
    whatsapp: '',
    whatsappCountry: 'ID',
    destination: '',
    date: '',
    pax: 2,
    pickup: '',
    travelerType: 'international',
    currency: 'USD',
    addOns: [],
    notes: '',
    voucher: 'BROMO10',
    ...defaults,
    ...draft,
    travelerType: draft.travelerType ?? draft.traveler_type ?? defaults.travelerType ?? defaults.traveler_type ?? 'international',
    addOns: draft.addOns ?? draft.add_ons ?? defaults.addOns ?? defaults.add_ons ?? [],
    whatsappCountry: draft.whatsappCountry ?? draft.whatsapp_country ?? defaults.whatsappCountry ?? defaults.whatsapp_country ?? 'ID',
  };
}

function emptySummary(currency = 'USD') {
  return {
    currency,
    pax: 1,
    base: 0,
    basePrice: 0,
    subtotal: 0,
    discount: 0,
    total: 0,
    paymentGateway: '',
    paymentNote: '',
    usdPaymentNote: '',
    voucher: null,
    addOns: [],
    addOnsTotal: 0,
  };
}

function normalizeAvailability(availability = {}) {
  return {
    status: availability.status ?? 'available',
    seatsLeft: availability.seatsLeft ?? availability.seats_left ?? null,
    reason: availability.reason ?? '',
    capacityExceeded: availability.capacityExceeded ?? false,
  };
}

export function BookingProvider({ children }) {
  const { props } = usePage();
  const publicData = props.publicData ?? {};
  const pageBooking = props.booking?.draft ?? {};
  const bookingDefaults = publicData.bookingOptions?.initialBooking ?? {};
  const availableRoutes = props.routes ?? publicData.routes ?? [];
  const fallbackRoute = availableRoutes[0] ?? null;
  const initialRouteId = pageBooking.route ?? props.route?.id ?? fallbackRoute?.id;
  const initialBookingState = normalizeDraft(pageBooking, bookingDefaults);
  const [languageState, setLanguageState] = useState(props.language ?? publicData.language ?? 'us');
  const [selectedRouteId, setSelectedRouteId] = useState(initialRouteId);
  const [voucherCode, setVoucherCode] = useState(pageBooking.voucher ?? 'BROMO10');
  const [appliedVoucher, setAppliedVoucher] = useState(pageBooking.voucher ?? 'BROMO10');
  const [bookingCode] = useState(props.savedBooking?.code ?? '');
  const [booking, setBooking] = useState(initialBookingState);
  const language = normalizeRegion(languageState);
  const regionConfig = getRegionConfig(language);

  useEffect(() => {
    setBooking(normalizeDraft(pageBooking, bookingDefaults));
    setVoucherCode(pageBooking.voucher ?? bookingDefaults.voucher ?? '');
    setAppliedVoucher(pageBooking.voucher ?? bookingDefaults.voucher ?? '');
  }, [JSON.stringify(pageBooking), JSON.stringify(bookingDefaults)]);

  const selectedRoute = availableRoutes.find((route) => route.id === selectedRouteId || route.slug === selectedRouteId) ?? fallbackRoute;
  const t = { ...(copy[language] ?? copy.us ?? copy.id), regionId: language };
  const bookingSummary = props.booking?.summary ?? emptySummary(booking.currency);
  const dateAvailability = normalizeAvailability(props.booking?.availability);
  const bookingBlock = {
    blocked: dateAvailability.status === 'blocked',
    reason: dateAvailability.reason ?? '',
  };
  const whatsappUrl = props.whatsappUrl ?? publicData.whatsappUrl ?? publicData.site?.whatsappBaseUrl ?? '#';

  function setLanguage(nextLanguage) {
    const normalizedLanguage = normalizeRegion(nextLanguage);
    const nextRegionConfig = getRegionConfig(normalizedLanguage);

    setLanguageState(normalizedLanguage);
    router.visit(`/language/${normalizedLanguage}`, {
      preserveScroll: true,
      preserveState: true,
    });
    setBooking((current) => ({
      ...current,
      travelerType: nextRegionConfig.travelerType,
      currency: nextRegionConfig.currency,
    }));
  }

  function updateSelectedRouteId(routeId) {
    const nextRoute = availableRoutes.find((route) => route.id === routeId || route.slug === routeId) ?? fallbackRoute;
    const availableAddOns = new Set((nextRoute.addOns ?? []).map((addOn) => addOn.id));

    setSelectedRouteId(routeId);
    const nextBooking = {
      ...booking,
      route: routeId,
      addOns: booking.addOns.filter((addOnId) => availableAddOns.has(addOnId)),
    };
    setBooking(nextBooking);
    recalculateBooking(nextBooking, routeId);
  }

  function recalculateBooking(nextBooking = booking, routeId = selectedRouteId) {
    router.post('/booking/recalculate', {
      route: routeId,
      date: nextBooking.date,
      pax: nextBooking.pax,
      pickup: nextBooking.pickup,
      traveler_type: nextBooking.travelerType,
      currency: nextBooking.currency,
      add_ons: nextBooking.addOns,
      voucher: nextBooking.voucher ?? appliedVoucher,
    }, {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      only: ['booking', 'route', 'routes', 'publicData', 'errors'],
    });
  }

  function updateBooking(updates, { recalculate = false } = {}) {
    const nextBooking = {
      ...booking,
      ...updates,
    };

    setBooking(nextBooking);

    if (recalculate) {
      recalculateBooking(nextBooking);
    }
  }

  function mergeBooking(updater, options = {}) {
    const nextBooking = typeof updater === 'function'
      ? updater(booking)
      : {
          ...booking,
          ...updater,
        };

    updateBooking(nextBooking, options);
  }

  const value = {
    language,
    setLanguage,
    regionConfig,
    t,
    booking,
    setBooking: mergeBooking,
    updateBooking,
    recalculateBooking,
    routes: availableRoutes,
    publicData,
    selectedRoute,
    selectedRouteId,
    setSelectedRouteId: updateSelectedRouteId,
    voucherCode,
    setVoucherCode,
    appliedVoucher,
    setAppliedVoucher,
    bookingCode,
    bookingSummary,
    bookingBlock,
    dateAvailability,
    whatsappUrl,
  };

  return <BookingContext.Provider value={value}>{children}</BookingContext.Provider>;
}

export function useBooking() {
  const context = useContext(BookingContext);

  if (!context) {
    throw new Error('useBooking must be used inside BookingProvider');
  }

  return context;
}
