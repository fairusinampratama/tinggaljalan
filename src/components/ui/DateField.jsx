import { useEffect, useMemo, useRef, useState } from 'react';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { formatTravelDate } from '../../utils/date';

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

export function DateField({ label, value, onChange, language = 'en', className = '' }) {
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
    onChange(nextDate);
    setIsOpen(false);
  }

  return (
    <div ref={pickerRef} className={`relative ${className}`}>
      {label ? <span className="mb-2 block text-sm font-extrabold text-brandDark">{label}</span> : null}
      <button
        type="button"
        className={`flex min-h-10 w-full items-center justify-between gap-3 rounded-xl border border-brandLine bg-brandLight px-4 py-3 text-left text-sm font-bold text-brandDark outline-none transition duration-200 hover:-translate-y-0.5 hover:border-brandBlue/40 hover:bg-white hover:shadow-lg hover:shadow-brandBlue/10 focus:border-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue sm:min-h-11 ${
          isOpen ? 'border-brandBlue bg-white shadow-lg shadow-brandBlue/10' : ''
        }`}
        aria-expanded={isOpen}
        onClick={() => setIsOpen((current) => !current)}
      >
        <span className="truncate">{displayValue}</span>
        <CalendarDays className="h-4 w-4 shrink-0 text-brandBlue" />
      </button>

      {isOpen ? (
        <div className="absolute left-0 right-0 top-[calc(100%+8px)] z-[90] rounded-2xl border border-brandLine bg-white p-3 shadow-2xl shadow-black/12 sm:right-auto sm:w-80">
          <div className="mb-3 flex items-center justify-between gap-3">
            <button
              type="button"
              className="grid h-9 w-9 place-items-center rounded-xl border border-brandLine text-brandDark transition duration-200 hover:-translate-y-0.5 hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
              onClick={() => moveMonth(-1)}
              aria-label="Previous month"
            >
              <ChevronLeft className="h-4 w-4" />
            </button>
            <p className="text-center text-sm font-black text-brandDark">{monthLabel}</p>
            <button
              type="button"
              className="grid h-9 w-9 place-items-center rounded-xl border border-brandLine text-brandDark transition duration-200 hover:-translate-y-0.5 hover:border-brandBlue hover:bg-brandSoft hover:text-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
              onClick={() => moveMonth(1)}
              aria-label="Next month"
            >
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>

          <div className="grid grid-cols-7 gap-1 text-center">
            {weekdayLabels.map((day) => (
              <span key={day} className="py-1 text-[11px] font-black uppercase text-brandMuted">
                {day}
              </span>
            ))}
            {monthDays.map((day) => {
              const isSelected = day.iso === value;
              const isToday = day.iso === today;

              return (
                <button
                  key={day.iso}
                  type="button"
                  className={`grid aspect-square min-h-9 place-items-center rounded-xl text-xs font-black transition duration-200 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue ${
                    isSelected
                      ? 'bg-brandBlue text-white shadow-lg shadow-brandBlue/20'
                      : day.inMonth
                        ? 'text-brandDark hover:bg-brandSoft hover:text-brandBlue'
                        : 'text-brandMuted/40 hover:bg-brandLight'
                  } ${isToday && !isSelected ? 'ring-1 ring-brandBlue/30' : ''}`}
                  onClick={() => selectDate(day.iso)}
                >
                  {day.date.getDate()}
                </button>
              );
            })}
          </div>
        </div>
      ) : null}
    </div>
  );
}
