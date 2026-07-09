import { Compass, MapPin, ShieldCheck, Star } from 'lucide-react';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';

const iconMap = {
  compass: Compass,
  'map-pin': MapPin,
  'shield-check': ShieldCheck,
  star: Star,
};

export function TrustStripSection({ items }) {
  const { language, publicData } = useBooking();
  const trustItems = items ?? publicData.trustStats ?? [];

  if (!trustItems.length) {
    return null;
  }

  return (
    <section className="bg-white px-4 py-4 sm:px-8 sm:py-5 lg:px-10">
      <div className="mx-auto max-w-7xl rounded-xl border border-line bg-surface/85 p-2 shadow-soft">
        <div className="grid gap-1 sm:grid-cols-2 lg:grid-cols-4 lg:gap-0">
          {trustItems.map(({ title, value, icon }, index) => {
            const Icon = iconMap[icon] ?? Star;

            return (
              <article
                key={getLocalized(title, language)}
                className={`flex items-center gap-3 rounded-xl px-4 py-4 sm:px-5 lg:rounded-none lg:py-5 ${index > 0 ? 'lg:border-l lg:border-line' : ''}`}
              >
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-subtle text-secondary">
                  <Icon className="h-4 w-4" />
                </span>
                <div className="min-w-0">
                  <p className="font-display text-[1.35rem] font-bold leading-none tracking-normal text-ink sm:text-2xl">
                    {getLocalized(value, language)}
                  </p>
                  <p className="mt-1 text-xs font-semibold leading-5 text-muted sm:text-[13px]">{getLocalized(title, language)}</p>
                </div>
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
