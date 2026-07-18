import { useEffect, useId, useMemo, useRef, useState } from 'react';
import { CheckCircle, ChevronDown, Search } from 'lucide-react';

function normalizeOption(option) {
  if (typeof option === 'object') {
    return option;
  }

  return { label: option, value: option };
}

export function Dropdown({
  label,
  value,
  options,
  onChange,
  placeholder = 'Select',
  className = '',
  ariaLabel,
  triggerClassName = '',
  menuClassName = '',
  invalid = false,
  searchable = false,
  searchPlaceholder = 'Search',
  emptyMessage = 'No options found',
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [query, setQuery] = useState('');
  const dropdownRef = useRef(null);
  const searchRef = useRef(null);
  const listboxId = useId();
  const normalizedOptions = useMemo(() => options.map(normalizeOption), [options]);
  const selectedOption = normalizedOptions.find((option) => option.value === value);
  const visibleOptions = useMemo(() => {
    const needle = query.trim().toLocaleLowerCase();

    if (!needle) {
      return normalizedOptions;
    }

    return normalizedOptions.filter((option) =>
      [option.label, option.meta, option.selectedLabel, option.value]
        .filter(Boolean)
        .some((part) => String(part).toLocaleLowerCase().includes(needle))
    );
  }, [normalizedOptions, query]);

  function close() {
    setIsOpen(false);
    setQuery('');
  }

  useEffect(() => {
    function handlePointerDown(event) {
      if (!dropdownRef.current?.contains(event.target)) {
        close();
      }
    }

    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        close();
      }
    }

    document.addEventListener('pointerdown', handlePointerDown);
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('pointerdown', handlePointerDown);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, []);

  useEffect(() => {
    if (isOpen && searchable) {
      requestAnimationFrame(() => searchRef.current?.focus());
    }
  }, [isOpen, searchable]);

  return (
    <div ref={dropdownRef} className={`relative w-full min-w-0 max-w-full ${className}`}>
      {label ? <span className="mb-2 block text-sm font-semibold text-ink">{label}</span> : null}
      <button
        type="button"
        className={`flex w-full min-w-0 max-w-full items-center justify-between gap-3 rounded-xl border bg-canvas px-4 py-3 text-left text-sm font-semibold outline-none transition duration-200 hover:bg-surface hover:shadow-lg hover:shadow-secondary/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
          invalid ? 'border-red-400 focus:border-red-500 focus-visible:outline-red-500' : 'border-line hover:border-secondary/40 focus:border-secondary'
        } ${isOpen ? 'bg-surface shadow-lg shadow-secondary/10' : ''} ${triggerClassName}`}
        aria-label={ariaLabel}
        aria-invalid={invalid || undefined}
        aria-expanded={isOpen}
        aria-controls={listboxId}
        aria-haspopup="listbox"
        onClick={() => {
          if (isOpen) {
            close();
          } else {
            setIsOpen(true);
          }
        }}
      >
        <span className="min-w-0 flex-1">
          <span className="block truncate text-ink">{selectedOption?.selectedLabel ?? selectedOption?.label ?? placeholder}</span>
          {selectedOption?.meta && !selectedOption?.selectedLabel ? (
            <span className="mt-0.5 block truncate text-xs font-semibold text-muted">{selectedOption.meta}</span>
          ) : null}
        </span>
        <ChevronDown className={`h-4 w-4 shrink-0 text-muted transition ${isOpen ? 'rotate-180 text-secondary' : ''}`} />
      </button>

      {isOpen ? (
        <div
          className={`absolute left-0 ${menuClassName ? '' : 'right-0 max-w-full'} top-[calc(100%+8px)] z-[80] overflow-hidden rounded-xl border border-line bg-surface shadow-2xl shadow-black/12 ${menuClassName}`}
        >
          {searchable ? (
            <div className="sticky top-0 z-10 border-b border-line bg-surface p-2">
              <label className="flex items-center gap-2 rounded-lg border border-line bg-canvas px-3 focus-within:border-secondary focus-within:bg-surface">
                <Search className="h-4 w-4 shrink-0 text-muted" />
                <input
                  ref={searchRef}
                  type="search"
                  value={query}
                  className="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm font-semibold outline-none"
                  placeholder={searchPlaceholder}
                  aria-label={searchPlaceholder}
                  onChange={(event) => setQuery(event.target.value)}
                />
              </label>
            </div>
          ) : null}
          <div id={listboxId} role="listbox" className="max-h-72 overflow-auto p-2">
            {visibleOptions.length ? visibleOptions.map((option) => {
              const selected = option.value === value;

              return (
                <button
                  key={option.value}
                  type="button"
                  role="option"
                  aria-selected={selected}
                  className={`flex w-full min-w-0 items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
                    selected ? 'bg-subtle text-secondary' : 'text-ink hover:bg-canvas hover:text-secondary'
                  }`}
                  onClick={() => {
                    onChange(option.value);
                    close();
                  }}
                >
                  <span className="min-w-0 flex-1">
                    <span className="block truncate">{option.label}</span>
                    {option.meta ? <span className="mt-0.5 block truncate text-xs font-semibold text-muted">{option.meta}</span> : null}
                  </span>
                  {selected ? <CheckCircle className="h-4 w-4 shrink-0" /> : null}
                </button>
              );
            }) : (
              <p className="px-3 py-6 text-center text-sm font-semibold text-muted">{emptyMessage}</p>
            )}
          </div>
        </div>
      ) : null}
    </div>
  );
}
