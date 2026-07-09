import { useMemo } from 'react';
import PhoneNumberInput from 'react-phone-number-input/input';
import { getCountries, getCountryCallingCode, parsePhoneNumber } from 'react-phone-number-input';
import en from 'react-phone-number-input/locale/en';
import { Dropdown } from '../ui/Dropdown';

const countries = getCountries();
const countryOptions = countries
  .map((country) => ({
    value: country,
    label: en[country] ?? country,
    meta: `+${getCountryCallingCode(country)}`,
    selectedLabel: `${country} +${getCountryCallingCode(country)}`,
  }))
  .sort((left, right) => {
    if (left.value === 'ID') return -1;
    if (right.value === 'ID') return 1;
    return left.label.localeCompare(right.label);
  });

export function PhoneInput({
  value,
  country = 'ID',
  onChange,
  onCountryChange,
  invalid = false,
  required = false,
}) {
  const selectedCountry = countries.includes(country) ? country : 'ID';
  const parsed = useMemo(() => {
    try {
      return value ? parsePhoneNumber(value) : null;
    } catch {
      return null;
    }
  }, [value]);

  function changeCountry(nextCountry) {
    onCountryChange(nextCountry);

    if (parsed?.nationalNumber) {
      onChange(`+${getCountryCallingCode(nextCountry)}${parsed.nationalNumber}`);
    }
  }

  return (
    <div className="grid grid-cols-[7.5rem_minmax(0,1fr)] gap-2 sm:grid-cols-[8.5rem_minmax(0,1fr)]">
      <Dropdown
        value={selectedCountry}
        options={countryOptions}
        ariaLabel="WhatsApp country code"
        invalid={invalid}
        searchable
        searchPlaceholder="Search country"
        triggerClassName="h-full min-h-12 px-3 hover:translate-y-0"
        menuClassName="w-[min(20rem,calc(100vw-3rem))]"
        onChange={changeCountry}
      />
      <div className={`flex min-w-0 rounded-xl border bg-canvas transition focus-within:bg-surface ${
        invalid ? 'border-red-400 focus-within:border-red-500' : 'border-line hover:border-secondary/40 focus-within:border-secondary'
      }`}>
        <PhoneNumberInput
          country={selectedCountry}
          international={false}
          type="tel"
          inputMode="tel"
          autoComplete="tel-national"
          value={value || undefined}
          required={required}
          className="min-w-0 flex-1 border-0 bg-transparent px-4 py-3 text-sm font-bold outline-none"
          placeholder={selectedCountry === 'ID' ? '812 3456 7890' : 'Phone number'}
          aria-label="WhatsApp number"
          aria-invalid={invalid || undefined}
          onChange={(nextValue) => onChange(nextValue ?? '')}
        />
      </div>
    </div>
  );
}
