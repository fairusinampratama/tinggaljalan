import { usePage } from '@inertiajs/react';
import { BarChart3, Cookie, Database, MessageCircle, Settings2, ShieldCheck } from 'lucide-react';
import { Seo } from '../components/seo/Seo';
import { secondaryButtonClass } from '../components/ui/styles';
import { useBooking } from '../context/BookingContext';

export function PrivacyPolicyPage() {
  const { props } = usePage();
  const { language, t } = useBooking();
  const consentEnabled = typeof window !== 'undefined' && Boolean(window.TinggalJalanConsent);
  const sections = [
    { icon: ShieldCheck, title: t.privacyRequiredTitle, text: t.privacyRequiredText },
    { icon: BarChart3, title: t.privacyAdvertisingTitle, text: t.privacyAdvertisingText },
    { icon: Cookie, title: t.privacyGoogleTitle, text: t.privacyGoogleText },
    { icon: Settings2, title: t.privacyChoiceTitle, text: t.privacyChoiceText },
    { icon: Database, title: t.privacyRetentionTitle, text: t.privacyRetentionText },
    { icon: MessageCircle, title: t.privacyContactTitle, text: t.privacyContactText },
  ];

  return (
    <>
      <Seo {...(props.seo ?? {})} language={language} />
      <article className="bg-canvas pt-16 sm:pt-[72px]">
        <header className="border-b border-line bg-primary text-white">
          <div className="public-container px-4 py-12 sm:px-8 sm:py-16 lg:px-10 lg:py-20">
            <p className="public-eyebrow text-accent">{t.privacyEyebrow}</p>
            <h1 className="public-heading-hero mt-3 max-w-4xl text-white">{t.privacyTitle}</h1>
            <p className="mt-5 max-w-3xl text-base font-medium leading-7 text-white/75 sm:text-lg sm:leading-8">{t.privacyIntro}</p>
            <p className="mt-5 text-xs font-bold uppercase tracking-[0.08em] text-white/50">{t.privacyLastUpdated}</p>
          </div>
        </header>

        <div className="public-container px-4 py-10 sm:px-8 sm:py-14 lg:px-10 lg:py-16">
          <section className="rounded-2xl border border-secondary/25 bg-subtle p-5 sm:p-7" aria-labelledby="privacy-summary-title">
            <h2 id="privacy-summary-title" className="public-heading-card text-primary">{t.privacySummaryTitle}</h2>
            <p className="public-copy mt-3 max-w-4xl">{t.privacySummaryText}</p>
          </section>

          <div className="mt-8 grid gap-4 md:grid-cols-2 lg:mt-10 lg:gap-6">
            {sections.map(({ icon: Icon, title, text }) => (
              <section key={title} className="rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-7">
                <span className="grid h-10 w-10 place-items-center rounded-xl bg-subtle text-secondary" aria-hidden="true">
                  <Icon className="h-5 w-5" />
                </span>
                <h2 className="public-heading-card mt-5 text-primary">{title}</h2>
                <p className="public-copy mt-3">{text}</p>
              </section>
            ))}
          </div>

          {consentEnabled ? (
            <div className="mt-8 flex justify-center lg:mt-10">
              <button
                type="button"
                className={secondaryButtonClass}
                onClick={() => window.TinggalJalanConsent.openSettings()}
                data-testid="privacy-cookie-settings"
              >
                <Settings2 className="h-4 w-4" aria-hidden="true" /> {t.privacyOpenSettings}
              </button>
            </div>
          ) : null}
        </div>
      </article>
    </>
  );
}
