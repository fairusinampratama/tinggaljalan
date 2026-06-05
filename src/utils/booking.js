import { blockedBookingRules, vouchers } from '../data/bookingOptions';

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
  const rule = blockedBookingRules.find(
    (item) => item.routeDestination === route.destination && item.date === date,
  );

  return {
    blocked: Boolean(rule),
    reason: rule?.reason ?? '',
  };
}
