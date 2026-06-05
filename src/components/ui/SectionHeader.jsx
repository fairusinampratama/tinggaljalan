export function SectionHeader({ eyebrow, title, children }) {
  return (
    <div className="mx-auto mb-9 max-w-3xl text-center">
      <p className="mb-3 text-xs font-black uppercase tracking-[0.2em] text-brandBlue">{eyebrow}</p>
      <h2 className="font-display text-3xl font-black leading-tight text-brandDark sm:text-4xl">{title}</h2>
      {children ? <p className="mt-4 text-base leading-7 text-brandMuted">{children}</p> : null}
    </div>
  );
}
