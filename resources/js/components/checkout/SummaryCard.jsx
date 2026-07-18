import { AlertTriangle, CreditCard, MessageCircle, Users } from 'lucide-react';
import { formatCurrency } from '../../utils/currency';
import { formatTravelDate } from '../../utils/date';
import { getLocalized } from '../../utils/localization';
import { cardHoverClass } from '../ui/styles';

export function SummaryCard({ t, booking, selectedRoute, summary, bookingBlock, dateAvailability, showBlock = true, language, whatsappUrl = '#', onReduceGuests }) {
  const availabilityLabel = dateAvailability ? (t[dateAvailability.status] ?? dateAvailability.status) : null;

  return (
    <aside className={`h-fit w-full min-w-0 max-w-full rounded-xl border border-line bg-surface p-5 shadow-soft sm:p-6 ${cardHoverClass}`}>
      <h2 className="text-2xl font-bold">{t.summary}</h2>
      <div className="mt-5 grid gap-3 text-sm font-semibold text-muted">
        <div className="flex min-w-0 items-start justify-between gap-4"><span className="min-w-0">{t.route}</span><span className="min-w-0 break-words text-right text-ink">{selectedRoute ? getLocalized(selectedRoute.title, language) : '-'}</span></div>
        <div className="flex min-w-0 items-start justify-between gap-4"><span className="min-w-0">{t.date}</span><span className="min-w-0 break-words text-right text-ink">{formatTravelDate(booking.date, language)}</span></div>
        {availabilityLabel ? <div className="flex min-w-0 items-start justify-between gap-4"><span className="min-w-0">{t.availability}</span><span className="min-w-0 break-words text-right text-ink">{availabilityLabel}</span></div> : null}
        {booking.pickup ? <div className="flex min-w-0 items-start justify-between gap-4"><span className="min-w-0">{t.pickup}</span><span className="min-w-0 break-words text-right text-ink">{booking.pickup}</span></div> : null}
        <div className="flex min-w-0 items-start justify-between gap-4"><span className="min-w-0">{t.pax}</span><span className="shrink-0 text-right text-ink">{booking.pax}</span></div>
        <div className="flex min-w-0 items-start justify-between gap-4"><span className="min-w-0">{t.travelerType}</span><span className="min-w-0 break-words text-right text-ink">{booking.travelerType === 'international' ? t.travelerInternational : t.travelerLocal}</span></div>
        <div className="flex min-w-0 items-start justify-between gap-4"><span className="min-w-0">{t.currency}</span><span className="shrink-0 text-right text-ink">{summary.currency}</span></div>
        <div className="h-px bg-line" />
        {summary.quoteRequired ? (
          <div className="flex flex-col gap-4 rounded-xl border border-secondary/20 bg-secondary/5 p-4 text-secondary">
            <div className="flex min-w-0 items-center gap-2 font-bold text-ink">
              <Users className="h-5 w-5 text-secondary" />
              <span className="min-w-0 break-words">{t.quoteRequiredTitle ?? 'Custom group arrangement needed'}</span>
            </div>
            <p className="text-sm font-semibold leading-6 text-muted">
              {t.quoteRequiredText}
            </p>
            <div className="grid gap-2">
              <a
                href={whatsappUrl}
                target="_blank"
                rel="noreferrer"
                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-[#25D366] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#1ebe5d] hover:text-white"
              >
                <MessageCircle className="h-4 w-4" />
                {t.chatOnWhatsapp}
              </a>
              {onReduceGuests ? (
                <button
                  type="button"
                  className="inline-flex min-h-10 items-center justify-center rounded-xl border border-line bg-surface px-4 py-2 text-sm font-bold text-ink transition hover:border-secondary/40 hover:bg-subtle"
                  onClick={onReduceGuests}
                >
                  {t.reduceTravelers}
                </button>
              ) : null}
              <p className="text-xs font-semibold leading-5 text-muted">{t.customGroupContactFirst}</p>
            </div>
          </div>
        ) : (
          <>
            <div className="flex min-w-0 items-start justify-between gap-4">
              <span className="min-w-0 break-words">{t.perPersonPrice}</span>
              <span className="shrink-0">{formatCurrency(summary.unitPrice ?? summary.basePrice, summary.currency)}</span>
            </div>
            <div className="flex min-w-0 items-start justify-between gap-4"><span className="min-w-0 break-words">{t.packageSubtotal}</span><span className="shrink-0">{formatCurrency(summary.packageSubtotal, summary.currency)}</span></div>
            {summary.savingsPerPerson > 0 ? <div className="flex min-w-0 items-start justify-between gap-4 text-emerald-600"><span className="min-w-0 break-words">{t.savingsPerPerson}</span><span className="shrink-0">{formatCurrency(summary.savingsPerPerson, summary.currency)}</span></div> : null}
            {summary.addOns.length ? (
              <div className="grid gap-2">
                <div className="flex min-w-0 justify-between gap-4 font-bold text-ink"><span className="min-w-0">{t.addOns}</span><span className="shrink-0">{formatCurrency(summary.addOnsTotal, summary.currency)}</span></div>
                {summary.addOns.map((addOn) => (
                  <div key={addOn.id} className="flex min-w-0 items-start justify-between gap-4 text-xs">
                    <span className="min-w-0 break-words">{getLocalized(addOn.title, language)} x{addOn.quantity}</span>
                    <span className="shrink-0">{formatCurrency(addOn.total, summary.currency)}</span>
                  </div>
                ))}
              </div>
            ) : null}
            {summary.discount > 0 ? (
              <div className="flex min-w-0 items-start justify-between gap-4 text-secondary"><span className="min-w-0 break-words">{t.discount} ({summary.voucher?.label ?? '-'})</span><span className="shrink-0">-{formatCurrency(summary.discount, summary.currency)}</span></div>
            ) : null}
            <div className="h-px bg-line" />
            <div className="flex min-w-0 items-end justify-between gap-4 text-ink">
              <span className="text-xl font-bold">{t.total}</span>
              <span className="shrink-0 text-2xl font-bold text-secondary">{formatCurrency(summary.total, summary.currency)}</span>
            </div>
            <div className="rounded-xl border border-secondary/15 bg-subtle p-4">
              <div className="flex items-start gap-2 text-sm font-bold text-ink">
                <CreditCard className="mt-0.5 h-4 w-4 shrink-0 text-secondary" />
                <span className="min-w-0 break-words">{t.paymentAfterConfirmation}</span>
              </div>
              <div className="mt-2 grid gap-1.5 text-xs font-semibold leading-5 text-muted">
                {summary.paymentNote ? <p>{summary.paymentNote}</p> : null}
                {summary.currency === 'USD' && summary.usdPaymentNote ? <p className="text-amber-700">{summary.usdPaymentNote}</p> : null}
              </div>
            </div>
          </>
        )}
      </div>

      {showBlock && bookingBlock.blocked ? (
        <div className="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
          <p className="flex items-center gap-2 font-bold"><AlertTriangle className="h-4 w-4" /> {t.blockedTitle}</p>
          <p className="mt-2 text-sm font-semibold leading-6">{bookingBlock.reason}</p>
        </div>
      ) : null}
    </aside>
  );
}
