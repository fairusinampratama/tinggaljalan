import { useEffect, useId, useRef, useState } from 'react';
import { CheckCircle, ChevronDown } from 'lucide-react';

function normalizeOption(option) {
  if (typeof option === 'object') {
    return option;
  }

  return { label: option, value: option };
}

export function Dropdown({ label, value, options, onChange, placeholder = 'Select', className = '' }) {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);
  const listboxId = useId();
  const normalizedOptions = options.map(normalizeOption);
  const selectedOption = normalizedOptions.find((option) => option.value === value);

  useEffect(() => {
    function handlePointerDown(event) {
      if (!dropdownRef.current?.contains(event.target)) {
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

  return (
    <div ref={dropdownRef} className={`relative ${className}`}>
      {label ? <span className="mb-2 block text-sm font-semibold text-brandDark">{label}</span> : null}
      <button
        type="button"
        className={`flex w-full items-center justify-between gap-3 rounded-xl border border-brandLine bg-brandLight px-4 py-3 text-left text-sm font-semibold outline-none transition duration-200 hover:-translate-y-0.5 hover:border-brandBlue/40 hover:bg-white hover:shadow-lg hover:shadow-brandBlue/10 focus:border-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue ${
          isOpen ? 'border-brandBlue bg-white shadow-lg shadow-brandBlue/10' : ''
        }`}
        aria-expanded={isOpen}
        aria-controls={listboxId}
        aria-haspopup="listbox"
        onClick={() => setIsOpen((current) => !current)}
      >
        <span className="min-w-0">
          <span className="block truncate text-brandDark">{selectedOption?.label ?? placeholder}</span>
          {selectedOption?.meta ? <span className="mt-0.5 block truncate text-xs font-semibold text-brandMuted">{selectedOption.meta}</span> : null}
        </span>
        <ChevronDown className={`h-4 w-4 shrink-0 text-brandMuted transition ${isOpen ? 'rotate-180 text-brandBlue' : ''}`} />
      </button>

      {isOpen ? (
        <div
          id={listboxId}
          role="listbox"
          className="absolute left-0 right-0 top-[calc(100%+8px)] z-[80] max-h-72 overflow-auto rounded-xl border border-brandLine bg-white p-2 shadow-2xl shadow-black/12"
        >
          {normalizedOptions.map((option) => {
            const selected = option.value === value;

            return (
              <button
                key={option.value}
                type="button"
                role="option"
                aria-selected={selected}
                className={`flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition duration-200 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue ${
                  selected ? 'bg-brandSoft text-brandBlue' : 'text-brandDark hover:bg-brandLight hover:text-brandBlue'
                }`}
                onClick={() => {
                  onChange(option.value);
                  setIsOpen(false);
                }}
              >
                <span className="min-w-0">
                  <span className="block truncate">{option.label}</span>
                  {option.meta ? <span className="mt-0.5 block truncate text-xs font-semibold text-brandMuted">{option.meta}</span> : null}
                </span>
                {selected ? <CheckCircle className="h-4 w-4 shrink-0" /> : null}
              </button>
            );
          })}
        </div>
      ) : null}
    </div>
  );
}
