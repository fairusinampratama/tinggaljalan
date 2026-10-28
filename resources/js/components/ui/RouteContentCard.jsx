export function RouteContentCard({ children, className = '', ...props }) {
  return (
    <section className={`route-content-card ${className}`} data-route-content-card {...props}>
      {children}
    </section>
  );
}
