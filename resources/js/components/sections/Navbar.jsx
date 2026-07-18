import { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { Menu, X } from 'lucide-react';
import { languages } from '../../data/translations';
import { useBooking } from '../../context/BookingContext';
import { iconButtonClass } from '../ui/styles';

export function Navbar({ language, setLanguage, t }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const { publicData } = useBooking();
  const logoUrl = publicData.site?.logoUrl ?? '/images/logo-tj.png';
  const navItems = [
    { label: t.nav?.[1] ?? 'Destinations', to: '/#destination' },
    { label: t.nav?.[2] ?? 'Packages', to: '/routes' },
    { label: t.nav?.[3] ?? 'News', to: '/news' },
    publicData.site?.aboutEnabled ? { label: t.footerAbout ?? 'About', to: '/about-us' } : null,
    { label: t.nav?.[5] ?? 'Contact', to: '/#contact' },
  ].filter(Boolean);
  const bookingLabel = t.footerBookTrip ?? t.nav?.[4] ?? 'Book a trip';

  return (
    <nav className="fixed inset-x-0 top-0 z-50 border-b border-line bg-surface/95 backdrop-blur-xl">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-[72px] sm:px-8 lg:px-10">
        <Link to="/" className="flex items-center gap-3 transition duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary" aria-label="Tinggal Jalan home">
          <img src={logoUrl} alt="Tinggal Jalan" className="h-9 w-auto object-contain sm:h-10" />
        </Link>

        <div className="hidden items-center gap-5 text-sm font-bold lg:flex">
          {navItems.map((item) => (
            <NavLink key={item.to} to={item.to} className="border-b-2 border-transparent py-2 transition duration-200 hover:border-secondary hover:text-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary">
              {item.label}
            </NavLink>
          ))}
          <Link to="/booking" className="inline-flex min-h-10 items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary">{bookingLabel}</Link>
        </div>

        <div className="flex items-center gap-2">
          <div className="hidden rounded-full border border-line bg-surface p-1 sm:flex">
            {languages.map((item) => (
              <button
                key={item.id}
                type="button"
                className={`rounded-full px-3 py-1.5 text-xs font-bold transition duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
                  language === item.id ? 'bg-secondary text-white shadow-sm shadow-secondary/20' : 'text-muted hover:bg-subtle hover:text-ink'
                }`}
                onClick={() => setLanguage(item.id)}
              >
                {item.label}
              </button>
            ))}
          </div>
          <button
            type="button"
            className={`${iconButtonClass} h-9 w-9 lg:hidden`}
            onClick={() => setIsMenuOpen((current) => !current)}
            aria-label={isMenuOpen ? 'Close menu' : 'Open menu'}
          >
            {isMenuOpen ? <X className="h-4 w-4" /> : <Menu className="h-4 w-4" />}
          </button>
        </div>
      </div>

      {isMenuOpen ? (
        <div className="border-t border-line bg-surface px-4 py-4 lg:hidden">
          <div className="grid gap-2">
            {navItems.map((item) => (
              <Link key={item.to} to={item.to} className="rounded-xl px-3 py-3 text-sm font-bold transition duration-200 hover:bg-subtle hover:text-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary" onClick={() => setIsMenuOpen(false)}>
                {item.label}
              </Link>
            ))}
            <Link to="/booking" className="rounded-xl bg-primary px-3 py-3 text-center text-sm font-bold text-white transition hover:bg-secondary" onClick={() => setIsMenuOpen(false)}>{bookingLabel}</Link>
            <div className="mt-2 flex rounded-full border border-line p-1">
              {languages.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  className={`flex-1 rounded-full px-3 py-2 text-xs font-bold transition duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${language === item.id ? 'bg-secondary text-white shadow-sm shadow-secondary/20' : 'text-muted hover:bg-subtle hover:text-ink'}`}
                  onClick={() => setLanguage(item.id)}
                >
                  {item.label}
                </button>
              ))}
            </div>
          </div>
        </div>
      ) : null}
    </nav>
  );
}
