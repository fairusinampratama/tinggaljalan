import { useEffect, useMemo, useRef, useState } from 'react';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { formatTravelDate } from '../../utils/date';
import { availabilityForDate } from '../../utils/availability';

const weekdayLabels = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

function toIsoDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function parseIsoDate(value) {
  if (!value) {
    return null;
  }

  const date = new Date(`${value}T00:00:00`);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date;
}

function getMonthDays(monthDate) {
  const year = monthDate.getFullYear();
  const month = monthDate.getMonth();
  const firstDay = new Date(year, month, 1);
  const firstGridDay = new Date(firstDay);

  firstGridDay.setDate(firstDay.getDate() - firstDay.getDay());

  return Array.from({ length: 42 }, (_, index) => {
    const date = new Date(firstGridDay);
    date.setDate(firstGridDay.getDate() + index);

    return {
      date,
      iso: toIsoDate(date),
      inMonth: date.getMonth() === month,
    };
  });
}

function getLocale(language) {
  if (language === 'id') {
    return 'id-ID';
  }

  if (language === 'zh' || language === 'cn') {
    return 'zh-CN';
  }

  return 'en-US';
}

const statusStyles = {
  available: 'text-ink hover:bg-subtle hover:text-secondary',
  limited: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100',
  booked: 'cursor-not-allowed bg-muted/10 text-muted/40 line-through',
  blocked: 'cursor-not-allowed bg-red-50 text-red-400 line-through',
};

const statusLabels = {
  available: 'Available',
  limited: 'Limited',
  booked: 'Booked',
  blocked: 'Blocked',
};

export function DateField({ label, value, onChange, language = 'en', className = '', availabilityByDate = {}, availabilityRules = [], showLegend = false }) {
  const pickerRef = useRef(null);
  const selectedDate = parseIsoDate(value);
  const initialMonth = selectedDate ?? new Date();
  const [isOpen, setIsOpen] = useState(false);
  const [visibleMonth, setVisibleMonth] = useState(new Date(initialMonth.getFullYear(), initialMonth.getMonth(), 1));
  const monthDays = useMemo(() => getMonthDays(visibleMonth), [visibleMonth]);
  const locale = getLocale(language);
  const monthLabel = new Intl.DateTimeFormat(locale, {
    month: 'long',
    year: 'numeric',
  }).format(visibleMonth);
  const displayValue = formatTravelDate(value, language);
  const today = toIsoDate(new Date());

  useEffect(() => {
    function handlePointerDown(event) {
      if (!pickerRef.current?.contains(event.target)) {
        setIsOpen(false);
      }
    }

    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        setIsOpen(false);
      }
    }

    document.addEventListener('pointerdown', handlePointerDown);
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('pointerdown', handlePointerDown);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, []);

  function moveMonth(amount) {
    setVisibleMonth((current) => new Date(current.getFullYear(), current.getMonth() + amount, 1));
  }

  function selectDate(nextDate) {
    const availability = availabilityForDate(availabilityRules, nextDate, availabilityByDate);

    if (availability?.status === 'booked' || availability?.status === 'blocked') {
      return;
    }

    onChange(nextDate);
    setIsOpen(false);
  }

  return (
    <div ref={pickerRef} className={`relative w-full min-w-0 max-w-full ${className}`}>
      {label ? <span className="mb-2 block text-sm font-semibold text-ink">{label}</span> : null}
      <button
        type="button"
        className={`flex min-h-10 w-full min-w-0 max-w-full items-center justify-between gap-3 rounded-xl border border-line bg-canvas px-4 py-3 text-left text-sm font-semibold text-ink outline-none transition duration-200 hover:border-secondary/40 hover:bg-surface hover:shadow-lg hover:shadow-secondary/10 focus:border-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary sm:min-h-11 ${
          isOpen ? 'border-secondary bg-surface shadow-lg shadow-secondary/10' : ''
        }`}
        aria-expanded={isOpen}
        onClick={() => setIsOpen((current) => !current)}
      >
        <span className="min-w-0 flex-1 truncate">{displayValue}</span>
        <CalendarDays className="h-4 w-4 shrink-0 text-secondary" />
      </button>

      {isOpen ? (
        <div className="absolute left-0 right-0 top-[calc(100%+8px)] z-[90] rounded-xl border border-line bg-surface p-3 shadow-2xl shadow-black/12 sm:right-auto sm:w-80">
          <div className="mb-3 flex items-center justify-between gap-3">
            <button
              type="button"
              className="grid h-9 w-9 place-items-center rounded-xl border border-line text-ink transition duration-200 hover:border-secondary hover:bg-subtle hover:text-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
              onClick={() => moveMonth(-1)}
              aria-label="Previous month"
            >
              <ChevronLeft className="h-4 w-4" />
            </button>
            <p className="text-center text-sm font-bold text-ink">{monthLabel}</p>
            <button
              type="button"
              className="grid h-9 w-9 place-items-center rounded-xl border border-line text-ink transition duration-200 hover:border-secondary hover:bg-subtle hover:text-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary"
              onClick={() => moveMonth(1)}
              aria-label="Next month"
            >
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>

          <div className="grid grid-cols-7 gap-1 text-center">
            {weekdayLabels.map((day) => (
              <span key={day} className="py-1 text-[11px] font-semibold uppercase text-muted">
                {day}
              </span>
            ))}
            {monthDays.map((day) => {
              const isSelected = day.iso === value;
              const isToday = day.iso === today;
              const availability = availabilityForDate(availabilityRules, day.iso, availabilityByDate);
              const isDisabled = availability.status === 'booked' || availability.status === 'blocked';
              const dayStatusClass = day.inMonth
                ? statusStyles[availability.status] ?? statusStyles.available
                : 'text-muted/30 hover:bg-canvas';

              return (
                <button
                  key={day.iso}
                  type="button"
                  disabled={isDisabled}
                  title={availability.reason || statusLabels[availability.status] || statusLabels.available}
                  className={`grid aspect-square min-h-9 place-items-center rounded-xl text-xs font-semibold transition duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
                    isSelected
                      ? 'bg-secondary text-white shadow-lg shadow-secondary/20'
                      : dayStatusClass
                  } ${isToday && !isSelected ? 'ring-1 ring-secondary/30' : ''}`}
                  onClick={() => selectDate(day.iso)}
                >
                  {day.date.getDate()}
                </button>
              );
            })}
          </div>
          {showLegend ? (
            <div className="mt-4 grid grid-cols-2 gap-2 text-[11px] font-bold text-muted">
              {Object.entries(statusLabels).map(([status, text]) => (
                <span key={status} className="inline-flex items-center gap-2">
                  <span className={`h-2.5 w-2.5 rounded-full ${
                    status === 'available'
                      ? 'bg-secondary'
                      : status === 'limited'
                        ? 'bg-amber-400'
                        : status === 'booked'
                          ? 'bg-muted/35'
                          : 'bg-red-400'
                  }`} />
                  {text}
                </span>
              ))}
            </div>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}
