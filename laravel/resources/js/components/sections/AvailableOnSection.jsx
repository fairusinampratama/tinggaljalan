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
    <section className="relative overflow-hidden bg-white px-4 py-14 sm:px-8 lg:px-10">
      <div className="relative mx-auto max-w-7xl">
        <div className="max-w-2xl">
          <p className="mb-3 text-xs font-bold uppercase tracking-[0.04em] text-brandBlue">{t.availableOnEyebrow}</p>
          <h2 className="font-display text-[1.625rem] font-bold leading-tight text-brandDark sm:text-[2rem]">{t.availableOnTitle}</h2>
          <p className="mt-3 text-sm font-semibold leading-6 text-brandMuted">{t.availableOnText}</p>
        </div>
        <div className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {availablePlatforms.map((platform) => (
            <a
              key={platform.name}
              href={platform.url}
              target="_blank"
              rel="noreferrer"
              className={`group rounded-2xl border border-brandLine bg-white p-4 shadow-soft ${interactiveCardClass}`}
            >
              <div className="flex items-center justify-between gap-3">
                <img src={platform.logo} alt={platform.alt} className="h-10 max-w-[150px] object-contain" />
                <ExternalLink className="h-4 w-4 text-brandMuted transition group-hover:text-brandBlue" />
              </div>
              <p className="mt-5 text-lg font-bold text-brandDark">{platform.name}</p>
              <p className="mt-1 text-xs font-semibold text-brandMuted">Official travel marketplace</p>
            </a>
          ))}
        </div>
      </div>
    </section>
  );
}
