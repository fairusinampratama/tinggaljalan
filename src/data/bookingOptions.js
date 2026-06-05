export const destinationOptions = ['Bromo', 'Jogja', 'Tumpak Sewu', 'Medan'];

export const blockedBookingRules = [
  {
    routeDestination: 'Bromo',
    date: '2026-07-10',
    reason: 'Bromo is temporarily closed due to local regulation or weather conditions.',
  },
];

export const paxOptions = [
  { label: { id: '1 Orang', cn: '1 人', us: '1 Guest' }, value: 1 },
  { label: { id: '2 Orang', cn: '2 人', us: '2 Guests' }, value: 2 },
  { label: { id: '3 Orang', cn: '3 人', us: '3 Guests' }, value: 3 },
  { label: { id: '4 Orang', cn: '4 人', us: '4 Guests' }, value: 4 },
  { label: { id: '5 Orang', cn: '5 人', us: '5 Guests' }, value: 5 },
];

export const pickupOptions = [
  { label: { id: 'Area Hotel Malang', cn: 'Malang 酒店区域', us: 'Malang Hotel Area' }, value: 'Malang Hotel Area' },
  { label: { id: 'Bandara Surabaya', cn: 'Surabaya 机场', us: 'Surabaya Airport' }, value: 'Surabaya Airport' },
  { label: { id: 'Stasiun Malang', cn: 'Malang 火车站', us: 'Malang Train Station' }, value: 'Malang Train Station' },
  { label: { id: 'Titik Jemput Custom', cn: '自定义接送点', us: 'Custom Pickup Point' }, value: 'Custom Pickup Point' },
];

export const travelerTypeOptions = [
  {
    label: { id: 'WNI / Local - IDR', cn: '印尼本地旅客 - IDR', us: 'Indonesian / Local - IDR' },
    value: 'local',
    meta: { id: 'Midtrans setelah konfirmasi', cn: '确认后使用 Midtrans', us: 'Midtrans after confirmation' },
  },
  {
    label: { id: 'International - USD', cn: '国际旅客 - USD', us: 'International - USD' },
    value: 'international',
    meta: { id: 'Stripe setelah konfirmasi', cn: '确认后使用 Stripe', us: 'Stripe after confirmation' },
  },
];

export const paymentGateways = {
  local: 'Midtrans',
  international: 'Stripe',
};

export const vouchers = {
  BROMO10: { label: 'BROMO10', percent: 10, currencies: ['IDR', 'USD'] },
  TJHEMAT: { label: 'TJHEMAT', amount: 50000, currency: 'IDR' },
};

export const initialBooking = {
  name: '',
  email: '',
  whatsapp: '',
  destination: 'Bromo',
  date: '2026-06-25',
  pax: 2,
  pickup: 'Malang Hotel Area',
  travelerType: 'local',
  notes: '',
};
