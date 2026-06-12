import { whatsappNumber } from '../data/brand';
import { formatCurrency } from './currency';
import { formatTravelDate } from './date';
import { getLocalized, normalizeRegion } from './localization';

const whatsappCopy = {
  heading: {
    id: 'Hi Tinggal Jalan, saya ingin mengirim permintaan booking:',
    cn: '你好 Tinggal Jalan，我想提交预订请求：',
    us: 'Hi Tinggal Jalan, I would like to send a booking request:',
  },
  bookingCode: { id: 'Kode Booking', cn: '预订编号', us: 'Booking Code' },
  package: { id: 'Paket', cn: '套餐', us: 'Package' },
  date: { id: 'Tanggal', cn: '日期', us: 'Date' },
  availability: { id: 'Ketersediaan', cn: '可用性', us: 'Availability' },
  available: { id: 'Tersedia', cn: '可预订', us: 'Available' },
  limited: { id: 'Slot terbatas', cn: '名额有限', us: 'Limited seats' },
  booked: { id: 'Penuh', cn: '已订满', us: 'Fully booked' },
  blocked: { id: 'Diblokir', cn: '暂停预订', us: 'Blocked' },
  pax: { id: 'Peserta', cn: '人数', us: 'Guests' },
  pickup: { id: 'Titik Jemput', cn: '接送点', us: 'Pickup Point' },
  travelerType: { id: 'Tipe Traveler', cn: '旅客类型', us: 'Traveler Type' },
  currency: { id: 'Currency', cn: '币种', us: 'Currency' },
  addOns: { id: 'Add-ons', cn: '加选项目', us: 'Add-ons' },
  estimatedTotal: { id: 'Estimasi Total', cn: '预计总价', us: 'Estimated Total' },
  paymentGateway: { id: 'Payment setelah konfirmasi', cn: '确认后的付款方式', us: 'Payment Gateway After Confirmation' },
  voucher: { id: 'Voucher', cn: '优惠券', us: 'Voucher' },
  name: { id: 'Nama', cn: '姓名', us: 'Name' },
  whatsapp: { id: 'WhatsApp', cn: 'WhatsApp', us: 'WhatsApp' },
  email: { id: 'Email', cn: '邮箱', us: 'Email' },
  notes: { id: 'Catatan', cn: '备注', us: 'Notes' },
  status: {
    id: 'Status: Menunggu konfirmasi ketersediaan dari tim Tinggal Jalan.',
    cn: '状态：等待 Tinggal Jalan 团队确认可用性。',
    us: 'Status: Waiting for availability confirmation from the Tinggal Jalan team.',
  },
  local: { id: 'WNI / Local', cn: '印尼本地旅客', us: 'Indonesian / Local' },
  international: { id: 'International', cn: '国际旅客', us: 'International' },
};

export function createWhatsAppUrl({ route, booking, bookingCode, voucher, total, currency, paymentGateway, addOns = [], availability, language }) {
  const region = normalizeRegion(language);
  const availabilityStatus = availability?.status ?? 'available';
  const addOnText = addOns.length
    ? addOns.map((addOn) => `${getLocalized(addOn.title, region)} (${formatCurrency(addOn.total, currency)})`).join(', ')
    : '-';
  const message = [
    getLocalized(whatsappCopy.heading, region),
    `${getLocalized(whatsappCopy.bookingCode, region)}: ${bookingCode}`,
    `${getLocalized(whatsappCopy.package, region)}: ${getLocalized(route.title, region)}`,
    `${getLocalized(whatsappCopy.date, region)}: ${formatTravelDate(booking.date, region)}`,
    `${getLocalized(whatsappCopy.availability, region)}: ${getLocalized(whatsappCopy[availabilityStatus] ?? whatsappCopy.available, region)}`,
    `${getLocalized(whatsappCopy.pax, region)}: ${booking.pax}`,
    `${getLocalized(whatsappCopy.pickup, region)}: ${booking.pickup}`,
    `${getLocalized(whatsappCopy.travelerType, region)}: ${getLocalized(booking.travelerType === 'international' ? whatsappCopy.international : whatsappCopy.local, region)}`,
    `${getLocalized(whatsappCopy.currency, region)}: ${currency}`,
    `${getLocalized(whatsappCopy.addOns, region)}: ${addOnText}`,
    `${getLocalized(whatsappCopy.estimatedTotal, region)}: ${formatCurrency(total, currency)}`,
    `${getLocalized(whatsappCopy.paymentGateway, region)}: ${paymentGateway}`,
    `${getLocalized(whatsappCopy.voucher, region)}: ${voucher?.label ?? '-'}`,
    `${getLocalized(whatsappCopy.name, region)}: ${booking.name}`,
    `${getLocalized(whatsappCopy.whatsapp, region)}: ${booking.whatsapp}`,
    booking.email ? `${getLocalized(whatsappCopy.email, region)}: ${booking.email}` : null,
    booking.notes ? `${getLocalized(whatsappCopy.notes, region)}: ${booking.notes}` : null,
    getLocalized(whatsappCopy.status, region),
  ]
    .filter(Boolean)
    .join('\n');

  return `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
}
