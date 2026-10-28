import { getLocalized } from '../../utils/localization';

export function ItineraryTimeline({ title, items = [], language = 'us' }) {
  const multiDay = new Set(items.map(item => item.dayNumber)).size > 1;
  const dayLabel = language === 'id' ? 'Hari' : language === 'cn' ? '第' : 'Day';
  return (
    <section className="mt-8" aria-label={title} data-testid="itinerary-timeline">
      <h3 className="text-lg font-bold text-ink">{title}</h3>
      <ol className="mt-5 space-y-6">
        {items.map((item, index) => (
          <li key={item.id} className="relative pl-6">
            {index < items.length - 1 ? <span aria-hidden="true" className="absolute bottom-[-1.5rem] left-1 top-2 w-px bg-line" /> : null}
            <span aria-hidden="true" className="absolute left-0 top-2 h-[9px] w-[9px] rounded-full bg-secondary" />
            {multiDay && (index === 0 || items[index - 1].dayNumber !== item.dayNumber) ? (
              <p className="mb-3 text-sm font-semibold text-secondary">{dayLabel} {item.dayNumber}{language === 'cn' ? ' 天' : ''}</p>
            ) : null}
            <div className="grid gap-2 sm:grid-cols-[7rem_minmax(0,1fr)] sm:gap-5">
              <div>{item.timeLabel ? <span className="inline-block rounded-md bg-secondary/10 px-2.5 py-1 text-sm font-bold text-secondary">{item.timeLabel}</span> : null}</div>
              <div className="min-w-0 break-words">
                <p className="font-bold text-primary">{getLocalized(item.title, language)}</p>
                {getLocalized(item.description, language) ? <p className="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted">{getLocalized(item.description, language)}</p> : null}
              </div>
            </div>
          </li>
        ))}
      </ol>
    </section>
  );
}
