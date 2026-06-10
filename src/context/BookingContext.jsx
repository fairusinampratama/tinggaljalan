import { createContext, useContext, useMemo, useState } from 'react';
import { initialBooking } from '../data/bookingOptions';
import { getRouteById, routeArticles } from '../data/routes';
import { copy } from '../data/translations';
import { calculateBookingSummary, createBookingCode, getBookingBlock, getDateAvailability } from '../utils/booking';
import { getRegionConfig, normalizeRegion } from '../utils/localization';
import { createWhatsAppUrl } from '../utils/whatsapp';

const BookingContext = createContext(null);

export function BookingProvider({ children }) {
  const [languageState, setLanguageState] = useState('us');
  const [selectedRouteId, setSelectedRouteId] = useState(routeArticles[0].id);
  const [voucherCode, setVoucherCode] = useState('BROMO10');
  const [appliedVoucher, setAppliedVoucher] = useState('BROMO10');
  const [bookingCode] = useState(() => createBookingCode());
  const [booking, setBooking] = useState(initialBooking);
  const language = normalizeRegion(languageState);
  const regionConfig = getRegionConfig(language);

  const selectedRoute = getRouteById(selectedRouteId) ?? routeArticles[0];
  const t = { ...(copy[language] ?? copy.zh ?? copy.en), regionId: language };
  const bookingSummary = calculateBookingSummary(selectedRoute, booking.pax, appliedVoucher, booking.travelerType);
  const bookingBlock = getBookingBlock(selectedRoute, booking.date);
  const dateAvailability = getDateAvailability(selectedRoute, booking.date);
  const whatsappUrl = useMemo(
    () =>
      createWhatsAppUrl({
        route: selectedRoute,
        booking,
        bookingCode,
        voucher: bookingSummary.voucher,
        total: bookingSummary.total,
        currency: bookingSummary.currency,
        paymentGateway: bookingSummary.paymentGateway,
        availability: dateAvailability,
        language,
      }),
    [booking, bookingCode, bookingSummary.currency, bookingSummary.paymentGateway, bookingSummary.total, bookingSummary.voucher, dateAvailability, language, selectedRoute],
  );

  function setLanguage(nextLanguage) {
    const normalizedLanguage = normalizeRegion(nextLanguage);
    const nextRegionConfig = getRegionConfig(normalizedLanguage);

    setLanguageState(normalizedLanguage);
    setBooking((current) => ({
      ...current,
      travelerType: nextRegionConfig.travelerType,
    }));
  }

  const value = {
    language,
    setLanguage,
    regionConfig,
    t,
    booking,
    setBooking,
    selectedRoute,
    selectedRouteId,
    setSelectedRouteId,
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
