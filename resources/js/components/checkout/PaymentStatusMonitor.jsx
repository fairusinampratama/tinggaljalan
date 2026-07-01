import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const terminalStatuses = ['paid', 'expired', 'failed', 'cancelled'];

export function PaymentStatusMonitor({ payment }) {
  const terminal = terminalStatuses.includes(payment.status);
  const pollingStartedAt = useRef(Date.now());
  const [check, setCheck] = useState({ checking: !terminal, succeeded: true, checkedAt: null });

  useEffect(() => {
    if (terminal) {
      setCheck((current) => ({ ...current, checking: false }));
      return undefined;
    }

    let disposed = false;
    let timer = null;
    let controller = null;
    let inFlight = false;
    let reloading = false;

    function schedule() {
      if (disposed || reloading || document.hidden) return;
      const delay = Date.now() - pollingStartedAt.current < 120000 ? 5000 : 30000;
      timer = window.setTimeout(refresh, delay);
    }

    async function refresh() {
      if (disposed || inFlight || reloading || document.hidden) return;

      inFlight = true;
      controller = new AbortController();
      setCheck((current) => ({ ...current, checking: true }));

      try {
        const response = await fetch(`/checkout/payment/${encodeURIComponent(payment.publicToken)}/status`, {
          headers: { Accept: 'application/json' },
          signal: controller.signal,
        });

        if (!response.ok) throw new Error(`Payment status check failed with HTTP ${response.status}`);

        const result = await response.json();
        if (disposed) return;

        setCheck({ checking: false, succeeded: result.checkSucceeded, checkedAt: result.checkedAt });

        if (result.status !== payment.status) {
          reloading = true;
          router.reload({
            only: ['payment'],
            preserveScroll: true,
            onFinish: () => { reloading = false; },
          });
          return;
        }

        if (result.terminal) return;
      } catch (error) {
        if (error.name !== 'AbortError' && !disposed) {
          setCheck((current) => ({ ...current, checking: false, succeeded: false }));
        }
      } finally {
        inFlight = false;
        controller = null;
        schedule();
      }
    }

    function handleVisibility() {
      if (document.hidden) {
        window.clearTimeout(timer);
      } else {
        refresh();
      }
    }

    document.addEventListener('visibilitychange', handleVisibility);
    window.addEventListener('focus', refresh);
    refresh();

    return () => {
      disposed = true;
      window.clearTimeout(timer);
      controller?.abort();
      document.removeEventListener('visibilitychange', handleVisibility);
      window.removeEventListener('focus', refresh);
    };
  }, [payment.publicToken, payment.status, terminal]);

  if (terminal) return null;

  const checkedAt = check.checkedAt
    ? new Date(check.checkedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    : null;

  return (
    <div className={`mt-5 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm font-semibold ${
      check.succeeded
        ? 'border-brandBlue/20 bg-brandSoft text-brandMuted'
        : 'border-amber-200 bg-amber-50 text-amber-900'
    }`}>
      <RefreshCw className={`mt-0.5 h-4 w-4 shrink-0 ${check.checking ? 'animate-spin' : ''}`} />
      <div>
        <p>
          {check.succeeded
            ? (check.checking
              ? (payment.copy?.checking ?? 'Checking payment status automatically...')
              : (payment.copy?.checked ?? 'Payment status is checked automatically.'))
            : (payment.copy?.refresh_failed ?? "Unable to refresh right now; we'll retry automatically.")}
        </p>
        {checkedAt && check.succeeded ? <p className="mt-1 text-xs">{(payment.copy?.last_checked ?? 'Last checked at :time').replace(':time', checkedAt)}</p> : null}
      </div>
    </div>
  );
}
