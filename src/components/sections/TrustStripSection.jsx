import { homeTrustItems } from '../../data/home';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';

export function TrustStripSection() {
  const { language } = useBooking();

  return (
    <section className="bg-brandLight px-4 py-8 sm:px-8 lg:px-10">
      <div className="mx-auto max-w-7xl rounded-2xl border border-brandLine bg-white/85 p-2 shadow-soft">
        <div className="grid gap-1 sm:grid-cols-2 lg:grid-cols-4 lg:gap-0">
          {homeTrustItems.map(({ title, value, icon: Icon }, index) => (
            <article
              key={getLocalized(title, language)}
              className={`flex items-center gap-3 rounded-xl px-4 py-4 sm:px-5 lg:rounded-none lg:py-5 ${index > 0 ? 'lg:border-l lg:border-brandLine' : ''}`}
            >
              <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brandSoft text-brandBlue">
                <Icon className="h-4 w-4" />
              </span>
              <div className="min-w-0">
                <p className="font-display text-[1.35rem] font-extrabold leading-none tracking-normal text-brandDark sm:text-2xl">
                  {getLocalized(value, language)}
                </p>
                <p className="mt-1 text-xs font-semibold leading-5 text-brandMuted sm:text-[13px]">{getLocalized(title, language)}</p>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
