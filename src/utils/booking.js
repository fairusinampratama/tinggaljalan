import { blockedBookingRules, dateAvailabilityRules, vouchers } from '../data/bookingOptions';

export function getBookingCurrency(currency = 'IDR') {
  return currency === 'USD' ? 'USD' : 'IDR';
}

export function getRoutePrice(route, currency = 'IDR') {
  return getBookingCurrency(currency) === 'USD' ? route.basePriceUsd : route.basePriceIdr ?? route.basePrice;
}

export function getPaymentGateway(currency = 'IDR') {
  return getBookingCurrency(currency) === 'USD' ? 'Stripe' : 'Midtrans';
}

export function getSelectedAddOns(route, addOnIds = []) {
  const selectedIds = new Set(addOnIds);
  return (route.addOns ?? []).filter((addOn) => selectedIds.has(addOn.id));
}

export function getAddOnPrice(addOn, currency = 'IDR') {
  return getBookingCurrency(currency) === 'USD' ? addOn.priceUsd : addOn.priceIdr;
}

export function calculateBookingSummary(route, pax, voucherCode, currencyInput = 'IDR', addOnIds = []) {
  const currency = getBookingCurrency(currencyInput);
  const voucher = vouchers[voucherCode.trim().toUpperCase()];
  const appliedVoucher =
    voucher && (!voucher.currency || voucher.currency === currency) && (!voucher.currencies || voucher.currencies.includes(currency))
      ? voucher
      : null;
  const basePrice = getRoutePrice(route, currency);
  const subtotal = basePrice * pax;
  const addOns = getSelectedAddOns(route, addOnIds).map((addOn) => {
    const unitPrice = getAddOnPrice(addOn, currency);
    const quantity = addOn.pricing === 'perPax' ? pax : 1;

    return {
      ...addOn,
      unitPrice,
      quantity,
      total: unitPrice * quantity,
    };
  });
  const addOnsTotal = addOns.reduce((total, addOn) => total + addOn.total, 0);
  const preDiscountTotal = subtotal + addOnsTotal;
  const discount = appliedVoucher?.percent
    ? Math.round((preDiscountTotal * appliedVoucher.percent) / 100)
    : appliedVoucher?.amount ?? 0;

  return {
    voucher: appliedVoucher,
    currency,
    paymentGateway: getPaymentGateway(currency),
    basePrice,
    subtotal,
    addOns,
    addOnsTotal,
    discount,
    total: Math.max(preDiscountTotal - discount, 0),
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
