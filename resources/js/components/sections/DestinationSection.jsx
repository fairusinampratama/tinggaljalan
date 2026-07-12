import { useNavigate } from 'react-router-dom';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { ResponsiveImage } from '../ui/ResponsiveImage';
import { SectionHeader } from '../ui/SectionHeader';

export function DestinationSection({ items }) {
  const navigate = useNavigate();
  const { language, t, publicData } = useBooking();
  const destinationItems = items ?? publicData.destinations ?? [];

  if (!destinationItems.length) {
    return null;
  }

  function openDestinationRoutes(destinationName) {
    navigate(`/routes?destination=${encodeURIComponent(destinationName)}`);
  }

  return (
    <section id="destination" className="public-section relative overflow-hidden bg-white">
      <div className="mx-auto max-w-7xl">
        <SectionHeader eyebrow={t.destinationEyebrow} title={t.destinationTitle}>
          {t.destinationText}
        </SectionHeader>
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {destinationItems.map((item) => (
            <article
              key={item.name}
              role="link"
              tabIndex={0}
              className="group cursor-pointer overflow-hidden rounded-xl border border-line bg-surface shadow-soft transition duration-300 hover:border-secondary/40 hover:shadow-xl hover:shadow-secondary/10 focus-visible:border-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
              onClick={() => openDestinationRoutes(item.name)}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                  event.preventDefault();
                  openDestinationRoutes(item.name);
                }
              }}
            >
              <div className="overflow-hidden">
                <ResponsiveImage
                  src={item.image}
                  alt={item.name}
                  className="h-52 w-full object-cover transition duration-500 group-hover:scale-105"
                  sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
                  width={1200}
                  height={780}
                />
              </div>
              <div className="p-5">
                <p className="text-xs font-bold uppercase tracking-[0.04em] text-secondary">{item.region}</p>
                <h3 className="public-heading-card mt-2 transition duration-300 group-hover:text-secondary">{item.name}</h3>
                <p className="public-copy mt-3">{getLocalized(item.copy, language)}</p>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
