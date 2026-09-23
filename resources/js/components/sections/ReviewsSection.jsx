import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { SectionHeader } from '../ui/SectionHeader';
import { cardHoverClass } from '../ui/styles';
import { RatingDisplay } from '../ui/RatingDisplay';

export function ReviewsSection({ items }) {
  const { language, t, publicData } = useBooking();
  const reviews = items ?? publicData.reviews ?? [];

  if (!reviews.length) {
    return null;
  }

  return (
    <section id="reviews" className="public-section bg-white">
      <div className="mx-auto max-w-7xl">
        <SectionHeader eyebrow={t.reviewsEyebrow} title={t.reviewsTitle}>
          {t.reviewsText}
        </SectionHeader>
        <div className="grid gap-5 md:grid-cols-3">
          {reviews.map((review) => (
            <article key={review.name} className={`rounded-xl border border-line bg-surface p-6 shadow-soft ${cardHoverClass}`}>
              <RatingDisplay rating={review.rating} reviewCount={review.reviewCount} className="mb-4" />
              <p className="min-h-24 text-sm font-semibold leading-7 text-muted">"{getLocalized(review.text, language)}"</p>
              <p className="mt-5 text-lg font-bold text-ink">
                {review.name}, {getLocalized(review.origin, language)}
              </p>
              <p className="mt-1 text-xs font-bold text-muted">{getLocalized(review.source, language)}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
