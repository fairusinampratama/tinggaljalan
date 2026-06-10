import { homeTrustItems } from '../../data/home';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { cardHoverClass } from '../ui/styles';

export function TrustStripSection() {
  const { language } = useBooking();

  return (
    <section className="bg-white px-4 py-8 sm:px-8 lg:px-10">
      <div className="mx-auto grid max-w-7xl gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {homeTrustItems.map(({ title, value, icon: Icon }) => (
          <article key={getLocalized(title, language)} className={`flex items-center gap-3 rounded-xl border border-transparent border-b-brandLine bg-white px-3 py-4 ${cardHoverClass}`}>
            <Icon className="h-4 w-4 shrink-0 text-brandBlue" />
            <div>
              <p className="text-xl font-extrabold leading-none text-brandDark">{getLocalized(value, language)}</p>
              <p className="mt-1 text-xs font-semibold text-brandMuted">{getLocalized(title, language)}</p>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
