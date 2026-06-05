export function Field({ label, children }) {
  return (
    <label className="block">
      <span className="mb-2 block text-sm font-extrabold text-brandDark">{label}</span>
      {children}
    </label>
  );
}
