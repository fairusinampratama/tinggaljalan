import { Compass, MapPin, Users } from 'lucide-react';
import { useBooking } from '../../context/BookingContext';
import { getLocalized } from '../../utils/localization';
import { RatingDisplay } from '../ui/RatingDisplay';
import { SectionHeader } from '../ui/SectionHeader';

const iconMap = {
  compass: Compass,
  'map-pin': MapPin,
  users: Users,
};

export function SocialProofSection({ benefits, reviews: reviewItems }) {
  const { language, t, publicData } = useBooking();
  const benefitItems = benefits ?? publicData.home?.whyChooseItems ?? [];
  const reviews = reviewItems ?? publicData.reviews ?? [];

  if (!benefitItems.length && !reviews.length) {
    return null;
  }

  return (
    <section className="public-section bg-subtle">
      <div className="public-container">
        {benefitItems.length ? (
          <>
            <SectionHeader eyebrow={t.whyEyebrow} title={t.whyTitle}>
              {t.whyText}
            </SectionHeader>
            <div className="grid gap-4 md:grid-cols-3">
              {benefitItems.map(({ title, text, icon }) => {
                const Icon = iconMap[icon] ?? Compass;

                return (
                  <article key={getLocalized(title, language)} className="min-w-0 rounded-xl bg-white p-5 shadow-[0_8px_30px_rgba(16,42,54,0.06)] sm:p-6">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-secondary/10 text-secondary">
                      <Icon className="h-4 w-4" aria-hidden="true" />
                    </div>
                    <h3 className="public-heading-card mt-4">{getLocalized(title, language)}</h3>
                    <p className="public-copy mt-2">{getLocalized(text, language)}</p>
                  </article>
                );
              })}
            </div>
          </>
        ) : null}

        {reviews.length ? (
          <div className={benefitItems.length ? 'mt-10 border-t border-line pt-10 sm:mt-12 sm:pt-12' : ''}>
            <div className="mx-auto mb-7 max-w-2xl text-center sm:mb-9">
              <p className="public-eyebrow">{t.reviewsEyebrow}</p>
              <h2 className="public-heading-section mt-3">{t.reviewsTitle}</h2>
              <p className="public-copy mx-auto mt-3 max-w-xl">{t.reviewsText}</p>
            </div>
            <div className="grid gap-4 md:grid-cols-3">
              {reviews.map((review) => (
                <article key={review.name} className="min-w-0 rounded-xl border border-line bg-white p-5 sm:p-6">
                  <RatingDisplay rating={review.rating} reviewCount={review.reviewCount} className="mb-4" />
                  <blockquote className="public-copy">“{getLocalized(review.text, language)}”</blockquote>
                  <p className="mt-5 break-words font-semibold text-ink">
                    {review.name}, {getLocalized(review.origin, language)}
                  </p>
                  <p className="mt-1 text-xs font-semibold text-muted">{getLocalized(review.source, language)}</p>
                </article>
              ))}
            </div>
          </div>
        ) : null}
      </div>
    </section>
  );
}
