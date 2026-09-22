import { useEffect, useRef, useState } from 'react';
import { Cookie, ExternalLink } from 'lucide-react';
import { primaryButtonClass, secondaryButtonClass } from '../ui/styles';

function consentApi() {
  return typeof window === 'undefined' ? null : window.TinggalJalanConsent;
}

export function ConsentBanner({ t }) {
  const initialStatus = consentApi()?.status() ?? 'unknown';
  const [isOpen, setIsOpen] = useState(() => initialStatus === 'unknown');
  const [status, setStatus] = useState(initialStatus);
  const panelRef = useRef(null);
  const returnFocusRef = useRef(null);

  useEffect(() => {
    const api = consentApi();
    if (!api) return undefined;

    const openSettings = () => {
      returnFocusRef.current = document.activeElement;
      setStatus(api.status());
      setIsOpen(true);
      window.requestAnimationFrame(() => panelRef.current?.focus());
    };
    const closeAfterDecision = (event) => {
      setStatus(event.detail?.status ?? api.status());
      setIsOpen(false);
      window.requestAnimationFrame(() => returnFocusRef.current?.focus?.());
    };

    window.addEventListener('tinggaljalan:consent-settings', openSettings);
    window.addEventListener('tinggaljalan:consent-change', closeAfterDecision);

    return () => {
      window.removeEventListener('tinggaljalan:consent-settings', openSettings);
      window.removeEventListener('tinggaljalan:consent-change', closeAfterDecision);
    };
  }, []);

  if (!isOpen || !consentApi()) return null;

  const statusLabel = status === 'granted'
    ? (t.cookieConsentStatusAllowed ?? 'Allowed')
    : (t.cookieConsentStatusDeclined ?? 'Declined');

  return (
    <section
      ref={panelRef}
      tabIndex={-1}
      aria-labelledby="cookie-consent-title"
      aria-describedby="cookie-consent-description"
      className="fixed inset-x-3 bottom-3 z-[70] mx-auto max-w-6xl rounded-xl border border-line bg-surface p-4 text-ink shadow-2xl outline-none focus-visible:ring-2 focus-visible:ring-accent sm:bottom-5 sm:p-5"
      data-testid="consent-banner"
    >
      <div className="flex items-start gap-3 lg:items-center">
        <span className="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-subtle text-secondary" aria-hidden="true">
          <Cookie className="h-4 w-4" />
        </span>
        <div className="min-w-0 flex-1 lg:flex lg:items-center lg:gap-6">
          <div className="min-w-0 flex-1">
            <h2 id="cookie-consent-title" className="font-display text-lg font-medium leading-7">{t.cookieConsentTitle ?? 'Your privacy choices'}</h2>
            <p id="cookie-consent-description" className="mt-1 text-sm font-medium leading-6 text-muted">
              {t.cookieConsentText ?? 'We use optional advertising cookies to understand which campaigns help travelers find us.'}{' '}
              <a href="/privacy-policy" className="inline-flex items-center gap-1 font-bold text-secondary underline decoration-secondary/40 underline-offset-2 hover:text-primary">
                {t.cookieConsentLearnMore ?? 'Learn more'} <ExternalLink className="h-3 w-3" aria-hidden="true" />
              </a>
            </p>
            {status !== 'unknown' ? (
              <p className="mt-1 text-xs font-bold text-muted" role="status" data-testid="consent-status">
                {(t.cookieConsentCurrentStatus ?? 'Current choice: {status}').replace('{status}', statusLabel)}
              </p>
            ) : null}
          </div>
          <div className="mt-4 grid grid-cols-2 gap-2 sm:flex sm:justify-end lg:mt-0 lg:shrink-0">
            <button
              type="button"
              className={`${secondaryButtonClass} w-full sm:min-w-28`}
              onClick={() => consentApi()?.deny()}
              aria-pressed={status === 'denied'}
              data-testid="consent-decline"
            >
              {t.cookieConsentDecline ?? 'Decline'}
            </button>
            <button
              type="button"
              className={`${primaryButtonClass} w-full sm:min-w-28`}
              onClick={() => consentApi()?.grant()}
              aria-pressed={status === 'granted'}
              data-testid="consent-allow"
            >
              {t.cookieConsentAllow ?? 'Accept'}
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}
