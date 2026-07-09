import { Compass, MapPin, Users } from 'lucide-react';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { SectionHeader } from '../ui/SectionHeader';
import { cardHoverClass } from '../ui/styles';

const iconMap = {
  compass: Compass,
  'map-pin': MapPin,
  users: Users,
};

export function WhyChooseSection({ items }) {
  const { language, t, publicData } = useBooking();
  const whyChooseItems = items ?? publicData.home?.whyChooseItems ?? [];

  if (!whyChooseItems.length) {
    return null;
  }

  return (
    <section className="public-section bg-white">
      <div className="mx-auto max-w-7xl">
        <SectionHeader eyebrow={t.whyEyebrow} title={t.whyTitle}>
          {t.whyText}
        </SectionHeader>
        <div className="grid gap-5 md:grid-cols-3">
          {whyChooseItems.map(({ title, text, icon }) => {
            const Icon = iconMap[icon] ?? Compass;

            return (
              <article key={getLocalized(title, language)} className={`rounded-xl border border-line bg-surface p-6 ${cardHoverClass}`}>
                <h3 className="flex items-center gap-2 text-xl font-bold text-ink">
                  <Icon className="h-4 w-4 text-secondary" /> {getLocalized(title, language)}
                </h3>
                <p className="mt-3 text-sm font-semibold leading-6 text-muted">{getLocalized(text, language)}</p>
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
