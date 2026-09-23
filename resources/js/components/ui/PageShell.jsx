export function PageShell({ eyebrow, title, children, actions }) {
  return (
    <section className="w-full min-w-0 bg-white px-4 pb-14 pt-24 sm:px-8 sm:pb-16 sm:pt-28 lg:px-10">
      <div className="mx-auto min-w-0 max-w-7xl">
        <div className="mb-8 min-w-0 max-w-3xl">
          {eyebrow ? <p className="mb-3 text-xs font-bold uppercase tracking-[0.04em] text-secondary">{eyebrow}</p> : null}
          <h1 className="public-heading-section text-ink">{title}</h1>
          {actions ? <div className="mt-6 flex flex-wrap gap-3">{actions}</div> : null}
        </div>
        {children}
      </div>
    </section>
  );
}
