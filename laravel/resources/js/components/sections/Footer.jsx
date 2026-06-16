import { ArrowUp, Mail, MessageCircle } from 'lucide-react';
import { useBooking } from '../../context/BookingContext';
import { iconSize, secondaryButtonClass, whatsappButtonClass } from '../ui/styles';

export function Footer({ t, whatsappUrl }) {
  const { publicData } = useBooking();
  const site = publicData.site ?? {};
  const contactDetails = site.contactDetails ?? {};
  const logoUrl = site.logoUrl ?? '/images/logo-tj.png';
  const trustBadges = site.trustBadges ?? [];
  const footerColumns = [
    {
      title: t.footerExplore,
      links: [
        { label: t.footerRoutes, href: '/routes' },
        { label: t.footerNews, href: '/news' },
        { label: t.footerBookTrip, href: '/booking' },
        { label: t.nav?.[1] ?? 'Destination', href: '/#destination' },
      ],
    },
    {
      title: t.footerSupport,
      links: [
        { label: t.footerFaq, href: '/#faq' },
        { label: t.nav?.[5] ?? 'Contact', href: '#contact' },
        { label: t.email, href: contactDetails.emailHref ?? `mailto:${contactDetails.email ?? ''}` },
      ],
    },
  ];

  return (
    <footer id="contact" className="bg-brandDark px-4 py-10 text-white sm:px-8 lg:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="grid gap-8 lg:grid-cols-[1.25fr_0.9fr_1fr] lg:items-start">
          <div>
            <img src={logoUrl} alt="Tinggal Jalan" className="h-11 w-auto rounded bg-white px-3 py-2" />
            <p className="mt-4 max-w-lg text-sm font-semibold leading-6 text-white/65">{t.footerText}</p>
            <div className="mt-4 flex flex-wrap gap-2">
              {trustBadges.map((item) => (
                <span key={item} className="rounded-full border border-white/15 px-3 py-1.5 text-[11px] font-bold text-white/75">
                  {item}
                </span>
              ))}
            </div>
          </div>

          <nav className="grid grid-cols-2 gap-6" aria-label="Footer navigation">
            {footerColumns.map((column) => (
              <div key={column.title}>
                <h3 className="text-sm font-bold text-white">{column.title}</h3>
                <ul className="mt-3 grid gap-2 text-sm font-semibold text-white/65">
                  {column.links.map((link) => (
                    <li key={`${column.title}-${link.label}`}>
                      <a
                        href={link.href}
                        target={link.external ? '_blank' : undefined}
                        rel={link.external ? 'noreferrer' : undefined}
                        className="inline-flex items-center gap-1 transition hover:-translate-y-0.5 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
                      >
                        {link.label}
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </nav>

          <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
            <p className="text-xs font-bold uppercase tracking-[0.04em] text-white/58">{t.contactEyebrow}</p>
            <h2 className="mt-2 text-2xl font-bold leading-tight">{t.contactTitleFooter}</h2>
            <p className="mt-2 text-sm font-semibold leading-6 text-white/65">{t.contactTextFooter}</p>
            <div className="mt-5 flex flex-col gap-3">
              <a href={whatsappUrl} target="_blank" rel="noreferrer" className={whatsappButtonClass}>
                <MessageCircle className={iconSize} /> {t.sendToWhatsapp ?? 'WhatsApp'}
              </a>
              <a href={contactDetails.emailHref ?? `mailto:${contactDetails.email ?? ''}`} className={`${secondaryButtonClass} border-white/15 bg-white/10 text-white hover:bg-white hover:text-brandDark`}>
                <Mail className={iconSize} /> {contactDetails.email}
              </a>
            </div>
          </div>
        </div>

        <div className="mt-8 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs font-semibold text-white/50 sm:flex-row sm:items-center sm:justify-between">
          <p>© 2026 Tinggal Jalan. All rights reserved.</p>
          <a
            href="#home"
            className="inline-flex items-center gap-1 transition hover:-translate-y-0.5 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue"
          >
            {t.backToTop} <ArrowUp className="h-4 w-4" />
          </a>
        </div>
      </div>
    </footer>
  );
}
