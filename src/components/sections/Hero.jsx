import { MessageCircle, Route, Search } from 'lucide-react';
import { Link } from 'react-router-dom';
import { destinationOptions, paxOptions } from '../../data/bookingOptions';
import { getDestinationByName } from '../../data/destinations';
import { getLocalized } from '../../utils/localization';
import { DateField } from '../ui/DateField';
import { Dropdown } from '../ui/Dropdown';
import { cardHoverClass, darkButtonClass, glassButtonClass, iconSize, primaryButtonClass } from '../ui/styles';

export function Hero({ t, language, booking, setBooking, whatsappUrl }) {
  const selectedDestination = getDestinationByName(booking.destination);
  const routeSearchPath = selectedDestination ? `/routes?destination=${encodeURIComponent(selectedDestination.name)}` : '/routes';
  const localizedPaxOptions = paxOptions.map((option) => ({ ...option, label: getLocalized(option.label, language) }));

  return (
    <section id="home" className="relative overflow-visible bg-white">
      <div className="relative min-h-[620px]">
        <img src="/images/hero-bromo.jpg" alt="Bromo sunrise jeep route" className="absolute inset-0 h-full w-full object-cover" />
        <div className="absolute inset-0 bg-black/35" />
        <div className="route-line hidden lg:block" />
        <div className="absolute right-10 top-28 hidden h-24 w-24 rounded-[38%_62%_58%_42%] border border-white/30 bg-white/10 backdrop-blur-sm lg:block" />
        <div className="terrain-sweep terrain-sweep-light bottom-24 left-8 hidden h-16 w-44 blur-sm lg:block" />
        <div className="relative mx-auto flex min-h-[620px] max-w-7xl items-center px-4 pb-28 pt-20 text-white sm:px-8 sm:pt-24 lg:px-10">
          <div className="max-w-3xl">
            <p className="font-brand mb-5 inline-flex rounded-full bg-white px-5 py-2.5 text-sm uppercase tracking-[0.08em] text-brandBlue sm:text-[15px] lg:text-base">
              {t.heroTag}
            </p>
            <h1 className="font-display text-[2rem] font-extrabold leading-[1.08] sm:text-4xl lg:text-[2.5rem]">{t.heroTitle}</h1>
            <p className="mt-6 max-w-2xl text-sm font-semibold leading-7 text-white/90 sm:text-base">{t.heroText}</p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Link to="/routes" className={primaryButtonClass}>
                <Route className={iconSize} /> {t.exploreRoutes}
              </Link>
              <a href={whatsappUrl} target="_blank" rel="noreferrer" className={glassButtonClass}>
                <MessageCircle className={iconSize} /> {t.chat}
              </a>
            </div>
          </div>
        </div>
      </div>

      <div className="relative z-30 mx-auto -mt-10 max-w-6xl px-4 pb-28 sm:px-8 sm:pb-32 lg:px-10">
        <div className={`rounded-2xl border border-brandLine bg-white p-4 shadow-soft sm:p-5 ${cardHoverClass}`}>
          <p className="mb-4 text-lg font-extrabold text-brandDark">{t.searchTitle}</p>
          <div className="grid gap-3 md:grid-cols-4">
            <Dropdown value={booking.destination} options={destinationOptions} onChange={(destination) => setBooking((current) => ({ ...current, destination }))} />
            <DateField
              value={booking.date}
              language={language}
              onChange={(date) => setBooking((current) => ({ ...current, date }))}
            />
            <Dropdown value={booking.pax} options={localizedPaxOptions} onChange={(pax) => setBooking((current) => ({ ...current, pax }))} />
            <Link to={routeSearchPath} className={darkButtonClass}>
              <Search className={iconSize} /> {t.search}
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}
