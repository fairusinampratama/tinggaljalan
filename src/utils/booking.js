import { blockedBookingRules, dateAvailabilityRules, vouchers } from '../data/bookingOptions';

export function getBookingCurrency(travelerType = 'local') {
  return travelerType === 'international' ? 'USD' : 'IDR';
}

export function getRoutePrice(route, travelerType = 'local') {
  return getBookingCurrency(travelerType) === 'USD' ? route.basePriceUsd : route.basePriceIdr ?? route.basePrice;
}

export function getPaymentGateway(travelerType = 'local') {
  return travelerType === 'international' ? 'Stripe' : 'Midtrans';
}

export function calculateBookingSummary(route, pax, voucherCode, travelerType = 'local') {
  const currency = getBookingCurrency(travelerType);
  const voucher = vouchers[voucherCode.trim().toUpperCase()];
  const appliedVoucher =
    voucher && (!voucher.currency || voucher.currency === currency) && (!voucher.currencies || voucher.currencies.includes(currency))
      ? voucher
      : null;
  const basePrice = getRoutePrice(route, travelerType);
  const subtotal = basePrice * pax;
  const discount = appliedVoucher?.percent
    ? Math.round((subtotal * appliedVoucher.percent) / 100)
    : appliedVoucher?.amount ?? 0;

  return {
    voucher: appliedVoucher,
    currency,
    paymentGateway: getPaymentGateway(travelerType),
    basePrice,
    subtotal,
    discount,
    total: Math.max(subtotal - discount, 0),
  };
}

export function createBookingCode(date = new Date()) {
  const stamp = date.toISOString().slice(0, 10).replaceAll('-', '');
  const suffix = Math.random().toString(36).slice(2, 6).toUpperCase();

  return `TJ-${stamp}-${suffix}`;
}

export function getBookingBlock(route, date) {
  const availability = getDateAvailability(route, date);
  const rule = blockedBookingRules.find(
    (item) => item.routeDestination === route.destination && item.date === date,
  );
  const blocked = availability.status === 'blocked' || Boolean(rule);

  return {
    blocked,
    reason: availability.reason || rule?.reason || '',
  };
}

export function getDateAvailability(route, date) {
  const rule = dateAvailabilityRules.find(
    (item) => item.routeDestination === route.destination && item.date === date,
  );

  return {
    status: rule?.status ?? 'available',
    seatsLeft: rule?.seatsLeft ?? null,
    reason: rule?.reason ?? '',
  };
}

export function getAvailabilityByDate(route) {
  return dateAvailabilityRules
    .filter((item) => item.routeDestination === route.destination)
    .reduce((items, item) => {
      items[item.date] = {
        status: item.status,
        seatsLeft: item.seatsLeft ?? null,
        reason: item.reason ?? '',
      };
      return items;
    }, {});
}
