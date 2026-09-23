export function Field({ label, children }) {
  return (
    <label className="block min-w-0 max-w-full">
      <span className="mb-2 block text-sm font-semibold text-ink">{label}</span>
      {children}
    </label>
  );
}
