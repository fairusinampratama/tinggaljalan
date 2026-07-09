import { Link } from '@inertiajs/react';

export function Pagination({ links }) {
  if (!links || links.length <= 3) {
    return null; // Only Previous, 1, and Next
  }

  return (
    <div className="mt-12 flex flex-wrap items-center justify-center gap-2">
      {links.map((link, index) => {
        const isActive = link.active;
        
        let label = link.label;
        if (label.includes('&laquo;')) label = '«';
        if (label.includes('&raquo;')) label = '»';

        if (!link.url) {
          return (
            <span
              key={index}
              className="px-4 py-2 text-sm font-medium text-muted/50 rounded-xl bg-surface border border-transparent"
              dangerouslySetInnerHTML={{ __html: label }}
            />
          );
        }

        return (
          <Link
            key={index}
            href={link.url}
            className={`px-4 py-2 text-sm font-medium rounded-xl transition duration-300 ${
              isActive
                ? 'bg-secondary text-white shadow-sm'
                : 'bg-surface border border-line text-ink hover:border-secondary hover:text-secondary'
            }`}
            dangerouslySetInnerHTML={{ __html: label }}
          />
        );
      })}
    </div>
  );
}
