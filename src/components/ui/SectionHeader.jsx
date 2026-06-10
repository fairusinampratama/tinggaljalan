export function SectionHeader({ eyebrow, title, children }) {
  return (
    <div className="mx-auto mb-9 max-w-3xl text-center">
      <p className="mb-3 text-xs font-extrabold uppercase tracking-[0.18em] text-brandBlue">{eyebrow}</p>
      <h2 className="font-display text-[1.625rem] font-extrabold leading-tight text-brandDark sm:text-[2rem]">{title}</h2>
      {children ? <p className="mt-4 text-sm font-medium leading-6 text-brandMuted">{children}</p> : null}
    </div>
  );
}
