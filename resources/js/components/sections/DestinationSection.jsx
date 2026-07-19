import { ChevronRight } from 'lucide-react';
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

  function openDestinationRoutes(destination) {
    const destinationFilter = destination.slug ?? destination.id ?? destination.name;

    navigate(`/routes?destination=${encodeURIComponent(destinationFilter)}`);
  }

  return (
    <section id="destination" className="public-section relative overflow-hidden bg-white">
      <div className="mx-auto max-w-7xl">
        <SectionHeader eyebrow={t.destinationEyebrow} title={t.destinationTitle}>
          {t.destinationText}
        </SectionHeader>
        <div className="grid items-stretch gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {destinationItems.map((item) => (
            <article
              key={item.name}
              role="link"
              tabIndex={0}
              className="group flex h-full cursor-pointer flex-col overflow-hidden rounded-xl border border-line bg-surface shadow-soft transition duration-300 hover:border-secondary/40 hover:shadow-xl hover:shadow-secondary/10 focus-visible:border-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
              onClick={() => openDestinationRoutes(item)}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                  event.preventDefault();
                  openDestinationRoutes(item);
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
              <div className="flex min-w-0 flex-1 flex-col p-5">
                <p className="truncate text-xs font-bold uppercase tracking-[0.04em] text-secondary">{item.region}</p>
                <h3 className="public-heading-card mt-2 line-clamp-2 transition duration-300 group-hover:text-secondary sm:min-h-16">{item.name}</h3>
                <p className="public-copy mt-3 line-clamp-5 sm:min-h-[7.5rem]">{getLocalized(item.copy, language)}</p>
                <div className="mt-auto flex items-center justify-end gap-2 pt-5 text-sm font-bold text-secondary">
                  <span>{t.viewRoutes}</span>
                  <ChevronRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                </div>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
