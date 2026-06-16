import { MessageCircle, Route } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useBooking } from '../../context/BookingContext';
import { glassButtonClass, iconSize, primaryButtonClass } from '../ui/styles';

export function HomeCtaSection({ whatsappUrl }) {
  const { t } = useBooking();

  return (
    <section className="bg-brandDark px-4 py-14 text-white sm:px-8 lg:px-10">
      <div className="mx-auto flex max-w-7xl flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div className="max-w-2xl">
          <p className="mb-3 text-xs font-bold uppercase tracking-[0.04em] text-white/58">{t.ctaEyebrow}</p>
          <h2 className="font-display text-[1.625rem] font-bold leading-tight sm:text-[2rem]">{t.ctaTitle}</h2>
          <p className="mt-4 text-sm font-semibold leading-7 text-white/70">
            {t.ctaText}
          </p>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row">
          <Link to="/routes" className={primaryButtonClass}>
            <Route className={iconSize} /> {t.exploreRoutes}
          </Link>
          <a href={whatsappUrl} target="_blank" rel="noreferrer" className={glassButtonClass}>
            <MessageCircle className={iconSize} /> {t.chat}
          </a>
        </div>
      </div>
    </section>
  );
}
