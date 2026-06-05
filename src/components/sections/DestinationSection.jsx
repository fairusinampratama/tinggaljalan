import { useNavigate } from 'react-router-dom';
import { destinations } from '../../data/destinations';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { SectionHeader } from '../ui/SectionHeader';

export function DestinationSection() {
  const navigate = useNavigate();
  const { language, t } = useBooking();

  function openDestinationRoutes(destinationName) {
    navigate(`/routes?destination=${encodeURIComponent(destinationName)}`);
  }

  return (
    <section id="destination" className="px-4 pb-14 pt-24 sm:px-8 lg:px-10">
      <div className="mx-auto max-w-7xl">
        <SectionHeader eyebrow={t.destinationEyebrow} title={t.destinationTitle}>
          {t.destinationText}
        </SectionHeader>
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {destinations.map((item) => (
            <article
              key={item.name}
              role="link"
              tabIndex={0}
              className="group cursor-pointer overflow-hidden rounded-2xl border border-brandLine bg-white shadow-soft transition duration-300 hover:-translate-y-1 hover:border-brandBlue/40 hover:shadow-xl hover:shadow-brandBlue/10 focus-visible:-translate-y-1 focus-visible:border-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
              onClick={() => openDestinationRoutes(item.name)}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                  event.preventDefault();
                  openDestinationRoutes(item.name);
                }
              }}
            >
              <div className="overflow-hidden">
                <img
                  src={item.image}
                  alt={item.name}
                  className="h-52 w-full object-cover transition duration-500 group-hover:scale-105"
                />
              </div>
              <div className="p-5">
                <p className="text-xs font-black uppercase tracking-[0.16em] text-brandBlue">{item.region}</p>
                <h3 className="mt-2 font-display text-3xl font-black transition duration-300 group-hover:text-brandBlue">{item.name}</h3>
                <p className="mt-3 min-h-20 text-sm font-semibold leading-6 text-brandMuted">{getLocalized(item.copy, language)}</p>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
