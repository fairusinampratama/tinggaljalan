export function PageShell({ eyebrow, title, children, actions }) {
  return (
    <section className="px-4 pb-16 pt-28 sm:px-8 sm:pt-32 lg:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8 max-w-3xl">
          {eyebrow ? <p className="mb-3 text-xs font-black uppercase tracking-[0.2em] text-brandBlue">{eyebrow}</p> : null}
          <h1 className="font-display text-4xl font-black leading-none text-brandDark sm:text-5xl">{title}</h1>
          {actions ? <div className="mt-6 flex flex-wrap gap-3">{actions}</div> : null}
        </div>
        {children}
      </div>
    </section>
  );
}
