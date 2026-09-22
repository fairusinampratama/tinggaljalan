export function SectionHeader({ eyebrow, title, titleClassName = 'type-section-title', children }) {
  return (
    <div className="mx-auto mb-8 min-w-0 max-w-3xl text-center sm:mb-10">
      <p className="public-eyebrow text-secondary">{eyebrow}</p>
      <h2 className={`${titleClassName} mt-3 text-primary`}>{title}</h2>
      {children ? <p className="type-body mx-auto mt-3 max-w-2xl">{children}</p> : null}
    </div>
  );
}
