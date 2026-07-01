import { usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, Circle, Clock, CreditCard, MessageCircle, XCircle } from 'lucide-react';
import { PaymentStatusMonitor } from '../components/checkout/PaymentStatusMonitor';
import { PageShell } from '../components/ui/PageShell';
import { Seo } from '../components/seo/Seo';
import { cardHoverClass, primaryButtonClass, secondaryButtonClass, whatsappButtonClass } from '../components/ui/styles';
import { formatCurrency } from '../utils/currency';

const toneStyles = {
  info: 'border-brandBlue/30 bg-brandBlue/10 text-brandDark',
  success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
  warning: 'border-amber-200 bg-amber-50 text-amber-900',
  danger: 'border-red-200 bg-red-50 text-red-900',
};

const timelineIcon = {
  complete: CheckCircle,
  current: Clock,
  problem: AlertTriangle,
  upcoming: Circle,
};

const timelineStyles = {
  complete: 'border-emerald-200 bg-emerald-50 text-emerald-800',
  current: 'border-brandBlue bg-brandBlue text-brandDark',
  problem: 'border-amber-200 bg-amber-50 text-amber-900',
  upcoming: 'border-brandLine bg-white text-brandMuted',
};

export function CheckoutPaymentStatusPage() {
  const { props } = usePage();
  const payment = props.payment;
  const booking = payment.booking;
  const copy = payment.copy ?? {};
  const isUsdQuote = payment.quoteCurrency === 'USD';
  const StatusIcon = payment.tone === 'success' ? CheckCircle : payment.tone === 'danger' ? XCircle : payment.tone === 'warning' ? AlertTriangle : CreditCard;

  return (
    <>
      <Seo
        title={`Payment ${booking.code}`}
        description="Tinggal Jalan secure payment page."
        path={`/checkout/payment/${payment.publicToken}`}
        noindex
      />
      <PageShell eyebrow={copy.eyebrow ?? 'Payment'} title={copy.pay_securely ?? `Pay securely with ${payment.providerLabel ?? 'Midtrans'}`}>
        <div className="grid gap-8 lg:grid-cols-[1fr_0.8fr]">
          <section className={`rounded-2xl border border-brandLine bg-white p-5 shadow-soft sm:p-6 ${cardHoverClass}`}>
            <div className={`rounded-2xl border p-4 ${toneStyles[payment.tone] ?? toneStyles.info}`}>
              <div className="flex items-start gap-3">
                <div className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/70">
                  <StatusIcon className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-sm font-extrabold uppercase tracking-wide">{payment.statusLabel}</p>
                  <h2 className="mt-1 text-2xl font-extrabold">{booking.code}</h2>
                  <p className="mt-2 text-sm font-semibold leading-6">{payment.body}</p>
                </div>
              </div>
            </div>

            <div className="mt-5 grid gap-2 sm:grid-cols-4">
              {payment.timeline.map((step) => {
                const Icon = timelineIcon[step.state] ?? Circle;

                return (
                  <div key={step.label} className={`rounded-xl border px-3 py-3 text-sm font-bold ${timelineStyles[step.state] ?? timelineStyles.upcoming}`}>
                    <Icon className="mb-2 h-4 w-4" />
                    {step.label}
                  </div>
                );
              })}
            </div>

            <dl className="mt-5 grid gap-3 rounded-2xl bg-brandLight p-4 text-sm font-semibold sm:grid-cols-2">
              <div>
                <dt className="text-brandMuted">{copy.charge ?? `${payment.providerLabel ?? 'Midtrans'} charge`}</dt>
                <dd className="mt-1 font-extrabold text-brandDark">{formatCurrency(payment.chargeAmount, 'IDR')}</dd>
              </div>
              <div>
                <dt className="text-brandMuted">{copy.original_quote ?? 'Original quote'}</dt>
                <dd className="mt-1 font-extrabold text-brandDark">{formatCurrency(payment.quoteAmount, payment.quoteCurrency)}</dd>
              </div>
              <div>
                <dt className="text-brandMuted">{copy.expires ?? 'Payment expires'}</dt>
                <dd className="mt-1 font-extrabold text-brandDark">{payment.expiresAt ?? '-'}</dd>
              </div>
              <div>
                <dt className="text-brandMuted">{copy.paid_at ?? 'Paid at'}</dt>
                <dd className="mt-1 font-extrabold text-brandDark">{payment.paidAt ?? '-'}</dd>
              </div>
              {isUsdQuote ? (
                <div className="sm:col-span-2">
                  <dt className="text-brandMuted">{copy.exchange_rate ?? 'Exchange rate'}</dt>
                  <dd className="mt-1 font-extrabold text-brandDark">1 USD = {formatCurrency(payment.exchangeRate, 'IDR')}</dd>
                  <p className="mt-2 text-xs font-semibold leading-5 text-brandMuted">
                    {copy.usd_note ?? payment.usdNote ?? 'Midtrans charges in IDR.'}
                  </p>
                </div>
              ) : null}
            </dl>

            <PaymentStatusMonitor payment={payment} />

            {!payment.canPay && ['pending', 'invoice_sent'].includes(payment.status) ? (
              <div className="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-amber-900">
                {copy.missing_link ?? 'This payment request is missing a payment link. Please contact our team.'}
              </div>
            ) : null}

            <div className="mt-6 flex flex-wrap gap-3">
              {payment.canPay ? (
                <a href={payment.snapUrl} className={primaryButtonClass}>
                  <CreditCard className="h-4 w-4" /> {copy.pay_securely ?? `Pay securely with ${payment.providerLabel ?? 'Midtrans'}`}
                </a>
              ) : null}
              <a href={props.whatsappUrl} target="_blank" rel="noreferrer" className={whatsappButtonClass}>
                <MessageCircle className="h-4 w-4" /> {copy.ask_whatsapp ?? 'Ask on WhatsApp'}
              </a>
              <a href="/" className={secondaryButtonClass}>{copy.back_home ?? 'Back home'}</a>
            </div>
          </section>

          <aside className={`rounded-2xl border border-brandLine bg-white p-5 shadow-soft sm:p-6 ${cardHoverClass}`}>
            <h2 className="text-xl font-bold">{copy.booking_summary ?? 'Booking summary'}</h2>
            <dl className="mt-5 space-y-4 text-sm font-semibold">
              <div>
                <dt className="text-brandMuted">{copy.package ?? 'Package'}</dt>
                <dd className="mt-1 font-extrabold text-brandDark">{booking.packageTitle}</dd>
              </div>
              <div>
                <dt className="text-brandMuted">{copy.travel_date ?? 'Travel date'}</dt>
                <dd className="mt-1 font-extrabold text-brandDark">{booking.travelDate ?? '-'}</dd>
              </div>
              <div>
                <dt className="text-brandMuted">{copy.customer ?? 'Customer'}</dt>
                <dd className="mt-1 font-extrabold text-brandDark">{booking.name}</dd>
              </div>
              <div>
                <dt className="text-brandMuted">{copy.guests ?? 'Guests'}</dt>
                <dd className="mt-1 font-extrabold text-brandDark">{booking.pax}</dd>
              </div>
            </dl>
          </aside>
        </div>
      </PageShell>
    </>
  );
}