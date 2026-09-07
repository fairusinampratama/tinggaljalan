import { useEffect, useState } from 'react';
import { Cookie } from 'lucide-react';
import { primaryButtonClass, secondaryButtonClass } from '../ui/styles';

function consentApi() {
  return typeof window === 'undefined' ? null : window.TinggalJalanConsent;
}

export function ConsentBanner({ t }) {
  const [isOpen, setIsOpen] = useState(() => consentApi()?.status() === 'unknown');

  useEffect(() => {
    const api = consentApi();
    if (!api) return undefined;

    const openSettings = () => setIsOpen(true);
    const closeAfterDecision = () => setIsOpen(false);

    window.addEventListener('tinggaljalan:consent-settings', openSettings);
    window.addEventListener('tinggaljalan:consent-change', closeAfterDecision);

    return () => {
      window.removeEventListener('tinggaljalan:consent-settings', openSettings);
      window.removeEventListener('tinggaljalan:consent-change', closeAfterDecision);
    };
  }, []);

  if (!isOpen || !consentApi()) return null;

  return (
    <section
      aria-label={t.cookieConsentTitle ?? 'Privacy choices'}
      className="fixed inset-x-3 bottom-3 z-[70] mx-auto max-w-3xl rounded-lg border border-line bg-surface p-4 text-ink shadow-2xl sm:bottom-5 sm:p-5"
      data-testid="consent-banner"
    >
      <div className="flex items-start gap-3">
        <span className="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-subtle text-secondary" aria-hidden="true">
          <Cookie className="h-4 w-4" />
        </span>
        <div className="min-w-0 flex-1">
          <h2 className="text-base font-bold">{t.cookieConsentTitle ?? 'Your privacy choices'}</h2>
          <p className="mt-1 text-sm font-semibold leading-6 text-muted">
            {t.cookieConsentText ?? 'Allow advertising cookies to help us measure our campaigns. You can change this choice at any time.'}
          </p>
          <div className="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              className={secondaryButtonClass}
              onClick={() => consentApi()?.deny()}
              data-testid="consent-decline"
            >
              {t.cookieConsentDecline ?? 'Decline'}
            </button>
            <button
              type="button"
              className={primaryButtonClass}
              onClick={() => consentApi()?.grant()}
              data-testid="consent-allow"
            >
              {t.cookieConsentAllow ?? 'Allow'}
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}
