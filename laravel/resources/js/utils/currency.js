const currencyLocales = {
  IDR: 'id-ID',
  USD: 'en-US',
};

export function formatCurrency(value, currency = 'IDR') {
  return new Intl.NumberFormat(currencyLocales[currency] ?? 'id-ID', {
    style: 'currency',
    currency,
    maximumFractionDigits: currency === 'IDR' ? 0 : 2,
  }).format(value);
}
