import { ExternalLink } from 'lucide-react';
import { useBooking } from '../../context/BookingContext';
import { interactiveCardClass } from '../ui/styles';

export function AvailableOnSection({ items }) {
  const { t, publicData } = useBooking();
  const availablePlatforms = items ?? publicData.platformLinks ?? [];

  if (!availablePlatforms.length) {
    return null;
  }

  return (
    <section className="public-section bg-white">
      <div className="relative mx-auto max-w-7xl">
        <div className="max-w-2xl">
          <p className="public-eyebrow mb-3 text-secondary">{t.availableOnEyebrow}</p>
          <h2 className="type-section-title text-ink">{t.availableOnTitle}</h2>
          <p className="type-body-compact mt-3 text-muted">{t.availableOnText}</p>
        </div>
        <div className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {availablePlatforms.map((platform) => (
            <a
              key={platform.name}
              href={platform.url}
              target="_blank"
              rel="noreferrer"
              className={`group rounded-xl border border-line bg-surface p-4 shadow-soft ${interactiveCardClass}`}
            >
              <div className="flex items-center justify-between gap-3">
                <img src={platform.logo} alt={platform.alt} className="h-10 max-w-[150px] object-contain" />
                <ExternalLink className="h-4 w-4 text-muted transition group-hover:text-secondary" />
              </div>
              <p className="type-ui-title mt-5 text-ink">{platform.name}</p>
              <p className="type-meta mt-1 text-muted">Official travel marketplace</p>
            </a>
          ))}
        </div>
      </div>
    </section>
  );
}
