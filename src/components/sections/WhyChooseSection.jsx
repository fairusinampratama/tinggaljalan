import { whyChooseItems } from '../../data/home';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { SectionHeader } from '../ui/SectionHeader';
import { cardHoverClass } from '../ui/styles';

export function WhyChooseSection() {
  const { language, t } = useBooking();

  return (
    <section className="bg-white px-4 py-16 sm:px-8 lg:px-10">
      <div className="mx-auto max-w-7xl">
        <SectionHeader eyebrow={t.whyEyebrow} title={t.whyTitle}>
          {t.whyText}
        </SectionHeader>
        <div className="grid gap-5 md:grid-cols-3">
          {whyChooseItems.map(({ title, text, icon: Icon }) => (
            <article key={getLocalized(title, language)} className={`rounded-2xl border border-brandLine bg-white p-6 ${cardHoverClass}`}>
              <h3 className="flex items-center gap-2 text-xl font-extrabold text-brandDark">
                <Icon className="h-4 w-4 text-brandBlue" /> {getLocalized(title, language)}
              </h3>
              <p className="mt-3 text-sm font-semibold leading-6 text-brandMuted">{getLocalized(text, language)}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
