import { Star } from 'lucide-react';

const tealClass = 'text-[#00a680]';

export function RatingDisplay({ rating = 5, reviewCount = 0, size = 'sm', className = '' }) {
  const iconSize = size === 'md' ? 'h-5 w-5' : 'h-4 w-4';
  const roundedRating = Math.max(0, Math.min(5, Math.round(rating)));

  return (
    <div className={`flex items-center gap-2 ${className}`}>
      <div className={`flex gap-0.5 ${tealClass}`} aria-label={`${rating} out of 5 stars`}>
        {[1, 2, 3, 4, 5].map((item) => (
          <Star
            key={item}
            className={`${iconSize} ${item <= roundedRating ? 'fill-current' : ''}`}
          />
        ))}
      </div>
      {reviewCount ? <span className="text-sm font-semibold text-brandMuted">({reviewCount})</span> : null}
    </div>
  );
}
