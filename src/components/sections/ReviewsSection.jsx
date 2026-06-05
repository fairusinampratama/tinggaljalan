import { Star } from 'lucide-react';
import { homeReviews } from '../../data/home';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { SectionHeader } from '../ui/SectionHeader';
import { cardHoverClass } from '../ui/styles';

export function ReviewsSection() {
  const { language, t } = useBooking();

  return (
    <section id="reviews" className="px-4 py-16 sm:px-8 lg:px-10">
      <div className="mx-auto max-w-7xl">
        <SectionHeader eyebrow={t.reviewsEyebrow} title={t.reviewsTitle}>
          {t.reviewsText}
        </SectionHeader>
        <div className="grid gap-5 md:grid-cols-3">
          {homeReviews.map((review) => (
            <article key={review.name} className={`rounded-2xl border border-brandLine bg-white p-6 shadow-soft ${cardHoverClass}`}>
              <div className="mb-4 flex text-brandBlue">
                {[1, 2, 3, 4, 5].map((item) => (
                  <Star key={item} className="h-3.5 w-3.5 fill-current" />
                ))}
              </div>
              <p className="min-h-24 text-sm font-semibold leading-7 text-brandMuted">"{getLocalized(review.text, language)}"</p>
              <p className="mt-5 font-display text-xl font-black text-brandDark">
                {review.name}, {getLocalized(review.origin, language)}
              </p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
