import { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { Menu, X } from 'lucide-react';
import { logoUrl } from '../../data/brand';
import { languages } from '../../data/translations';
import { iconButtonClass } from '../ui/styles';

const navRoutes = ['/', '/#destination', '/routes', '/booking', '/#contact'];

export function Navbar({ language, setLanguage, t }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  return (
    <nav className="fixed inset-x-0 top-0 z-50 border-b border-brandLine bg-white/95 backdrop-blur-xl">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-[72px] sm:px-8 lg:px-10">
        <Link to="/" className="flex items-center gap-3 transition duration-200 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue" aria-label="Tinggal Jalan home">
          <img src={logoUrl} alt="Tinggal Jalan" className="h-9 w-auto object-contain sm:h-10" />
        </Link>

        <div className="hidden items-center gap-5 text-sm font-black lg:flex">
          {t.nav.map((item, index) => (
            <NavLink key={item} to={navRoutes[index]} className="border-b-2 border-transparent py-2 transition duration-200 hover:-translate-y-0.5 hover:border-brandBlue hover:text-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue">
              {item}
            </NavLink>
          ))}
        </div>

        <div className="flex items-center gap-2">
          <div className="hidden rounded-full border border-brandLine bg-white p-1 sm:flex">
            {languages.map((item) => (
              <button
                key={item.id}
                type="button"
                className={`rounded-full px-3 py-1.5 text-xs font-black transition duration-200 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue ${
                  language === item.id ? 'bg-brandBlue text-white shadow-sm shadow-brandBlue/20' : 'text-brandMuted hover:bg-brandSoft hover:text-brandBlue'
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
        <div className="border-t border-brandLine bg-white px-4 py-4 lg:hidden">
          <div className="grid gap-2">
            {t.nav.map((item, index) => (
              <Link key={item} to={navRoutes[index]} className="rounded-xl px-3 py-3 text-sm font-black transition duration-200 hover:-translate-y-0.5 hover:bg-brandSoft hover:text-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue" onClick={() => setIsMenuOpen(false)}>
                {item}
              </Link>
            ))}
            <div className="mt-2 flex rounded-full border border-brandLine p-1">
              {languages.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  className={`flex-1 rounded-full px-3 py-2 text-xs font-black transition duration-200 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue ${language === item.id ? 'bg-brandBlue text-white shadow-sm shadow-brandBlue/20' : 'text-brandMuted hover:bg-brandSoft hover:text-brandBlue'}`}
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
