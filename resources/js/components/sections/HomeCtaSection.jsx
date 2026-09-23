import { ExternalLink, MessageCircle, Route } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useBooking } from '../../context/BookingContext';
import { buttonClass, iconSize, whatsappButtonClass } from '../ui/styles';

export function HomeCtaSection({ whatsappUrl, items }) {
  const { t, publicData } = useBooking();
  const platforms = (items ?? publicData.platformLinks ?? []).slice(0, 4);

  return (
    <section className="bg-white px-4 pb-14 pt-0 sm:px-8 sm:pb-16 lg:px-10">
      <div className="mx-auto max-w-7xl">
        {platforms.length ? (
          <div className="grid min-w-0 gap-6 border-t border-line pt-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.5fr)] lg:items-center">
            <div className="min-w-0 max-w-xl">
              <p className="public-eyebrow text-secondary">{t.availableOnEyebrow}</p>
              <h2 className="mt-2 text-balance font-display text-[clamp(1.75rem,3.5vw,2.35rem)] leading-[1.1] text-primary">
                {t.availableOnTitle}
              </h2>
              <p className="public-copy mt-2 max-w-lg">{t.availableOnText}</p>
            </div>

            <div className="grid min-w-0 grid-cols-2 gap-3 lg:grid-cols-4">
              {platforms.map((platform) => (
                <a
                  key={platform.name}
                  href={platform.url}
                  target="_blank"
                  rel="noreferrer"
                  aria-label={platform.name}
                  className="group flex min-h-20 min-w-0 items-center justify-between gap-2 rounded-xl border border-line bg-white px-3 py-3 transition hover:border-secondary/50 hover:bg-subtle focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                >
                  <img
                    src={platform.logo}
                    alt={platform.alt}
                    className="h-8 min-w-0 max-w-[calc(100%-1.5rem)] object-contain object-left"
                  />
                  <ExternalLink className="h-3.5 w-3.5 shrink-0 text-muted transition group-hover:text-secondary" aria-hidden="true" />
                </a>
              ))}
            </div>
          </div>
        ) : null}

        <div className={[platforms.length ? 'mt-10 sm:mt-12' : '', 'rounded-2xl bg-primary px-5 py-8 text-white shadow-[0_18px_45px_rgba(16,42,54,0.14)] sm:px-8 sm:py-10 lg:px-12 lg:py-12'].join(' ')}>
          <div className="flex min-w-0 flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div className="min-w-0 max-w-2xl">
              <p className="public-eyebrow text-accent">{t.ctaEyebrow}</p>
              <h2 className="mt-3 text-balance font-display text-[clamp(2.25rem,5vw,3rem)] leading-[1.08]">
                {t.ctaTitle}
              </h2>
              <p className="mt-3 max-w-xl text-pretty text-sm leading-7 text-white/72">{t.ctaText}</p>
            </div>
            <div className="flex shrink-0 flex-col gap-3 sm:flex-row">
              <Link to="/routes" className={[buttonClass, 'bg-white text-primary shadow-sm hover:bg-subtle'].join(' ')}>
                <Route className={iconSize} /> {t.exploreRoutes}
              </Link>
              <a href={whatsappUrl} target="_blank" rel="noreferrer" className={whatsappButtonClass}>
                <MessageCircle className={iconSize} /> {t.chat}
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
