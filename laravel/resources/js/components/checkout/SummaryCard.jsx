import { AlertTriangle } from 'lucide-react';
import { formatCurrency } from '../../utils/currency';
import { formatTravelDate } from '../../utils/date';
import { getLocalized } from '../../utils/localization';
import { cardHoverClass } from '../ui/styles';

export function SummaryCard({ t, booking, selectedRoute, summary, bookingBlock, dateAvailability, showBlock = true, language }) {
  const availabilityLabel = dateAvailability ? (t[dateAvailability.status] ?? dateAvailability.status) : null;

  return (
    <aside className={`h-fit rounded-2xl border border-brandLine bg-white p-5 shadow-soft sm:p-6 ${cardHoverClass}`}>
      <h2 className="text-2xl font-bold">{t.summary}</h2>
      <div className="mt-5 grid gap-3 text-sm font-semibold text-brandMuted">
        <div className="flex justify-between gap-4"><span>{t.route}</span><span className="text-right text-brandDark">{getLocalized(selectedRoute.title, language)}</span></div>
        <div className="flex justify-between gap-4"><span>{t.date}</span><span className="text-right text-brandDark">{formatTravelDate(booking.date, language)}</span></div>
        {availabilityLabel ? <div className="flex justify-between gap-4"><span>{t.availability}</span><span className="text-right text-brandDark">{availabilityLabel}</span></div> : null}
        <div className="flex justify-between gap-4"><span>{t.pickup}</span><span className="text-right text-brandDark">{booking.pickup}</span></div>
        <div className="flex justify-between gap-4"><span>{t.pax}</span><span className="text-right text-brandDark">{booking.pax}</span></div>
        <div className="flex justify-between gap-4"><span>{t.travelerType}</span><span className="text-right text-brandDark">{booking.travelerType === 'international' ? t.travelerInternational : t.travelerLocal}</span></div>
        <div className="flex justify-between gap-4"><span>{t.currency}</span><span className="text-right text-brandDark">{summary.currency}</span></div>
        <div className="flex justify-between gap-4"><span>{t.paymentAfterConfirmation}</span><span className="text-right text-brandDark">{summary.paymentGateway}</span></div>
        <div className="h-px bg-brandLine" />
        <div className="flex justify-between"><span>{t.basePrice}</span><span>{formatCurrency(summary.basePrice, summary.currency)}</span></div>
        <div className="flex justify-between"><span>{t.subtotal}</span><span>{formatCurrency(summary.subtotal, summary.currency)}</span></div>
        {summary.addOns.length ? (
          <div className="grid gap-2">
            <div className="flex justify-between font-bold text-brandDark"><span>{t.addOns}</span><span>{formatCurrency(summary.addOnsTotal, summary.currency)}</span></div>
            {summary.addOns.map((addOn) => (
              <div key={addOn.id} className="flex justify-between gap-4 text-xs">
                <span>{getLocalized(addOn.title, language)} x{addOn.quantity}</span>
                <span>{formatCurrency(addOn.total, summary.currency)}</span>
              </div>
            ))}
          </div>
        ) : null}
        <div className="flex justify-between text-brandBlue"><span>{t.discount} ({summary.voucher?.label ?? '-'})</span><span>-{formatCurrency(summary.discount, summary.currency)}</span></div>
        <div className="h-px bg-brandLine" />
        <div className="flex items-end justify-between text-brandDark">
          <span className="text-xl font-bold">{t.total}</span>
          <span className="text-2xl font-extrabold text-brandBlue">{formatCurrency(summary.total, summary.currency)}</span>
        </div>
        <p className="rounded-xl bg-brandSoft px-4 py-3 text-xs font-bold leading-5 text-brandMuted">{t.estimateNote}</p>
      </div>

      {showBlock && bookingBlock.blocked ? (
        <div className="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700">
          <p className="flex items-center gap-2 font-bold"><AlertTriangle className="h-4 w-4" /> {t.blockedTitle}</p>
          <p className="mt-2 text-sm font-semibold leading-6">{bookingBlock.reason}</p>
        </div>
      ) : null}
    </aside>
  );
}
