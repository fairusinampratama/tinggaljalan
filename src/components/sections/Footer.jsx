import { ArrowUp, Building2, ChevronRight, Mail, MapPin, MessageCircle, Phone, ShieldCheck } from 'lucide-react';
import { contactDetails, logoUrl, trustBadges } from '../../data/brand';
import { getLocalized } from '../../utils/localization';
import { iconSize, whatsappButtonClass } from '../ui/styles';

export function Footer({ t, whatsappUrl }) {
  const language = t.regionId ?? 'id';
  const contactCards = [
    {
      title: t.contactWhatsappTitle,
      text: t.contactWhatsappText,
      value: contactDetails.whatsapp,
      href: whatsappUrl,
      icon: MessageCircle,
      primary: true,
      external: true,
    },
    {
      title: t.contactPhoneTitle,
      text: t.contactPhoneText,
      value: contactDetails.whatsapp,
      href: contactDetails.phoneHref,
      icon: Phone,
    },
    {
      title: t.contactEmailTitle,
      text: t.contactEmailText,
      value: contactDetails.email,
      href: contactDetails.emailHref,
      icon: Mail,
    },
    {
      title: t.contactBaseTitle,
      text: t.contactBaseText,
      value: 'Sawojajar, Malang',
      href: contactDetails.mapHref,
      icon: MapPin,
      external: true,
    },
  ];
  const footerColumns = [
    {
      title: t.footerExplore,
      links: [
        { label: t.footerRoutes, href: '/routes' },
        { label: 'Bromo', href: '/routes?destination=Bromo' },
        { label: 'Jogja', href: '/routes?destination=Jogja' },
        { label: 'Tumpak Sewu', href: '/routes?destination=Tumpak%20Sewu' },
        { label: 'Medan', href: '/routes?destination=Medan' },
      ],
    },
    {
      title: t.footerBooking,
      links: [
        { label: t.footerBookTrip, href: '/booking' },
        { label: t.footerHowBookingWorks, href: '/booking' },
        { label: t.footerPaymentAfterConfirmation, href: '/booking' },
      ],
    },
    {
      title: t.footerSupport,
      links: [
        { label: t.footerFaq, href: '/#faq' },
        { label: 'WhatsApp', href: whatsappUrl, external: true },
        { label: t.email, href: contactDetails.emailHref },
        { label: t.footerPickupHelp, href: whatsappUrl, external: true },
      ],
    },
    {
      title: t.footerCompany,
      links: [
        { label: t.footerAbout, href: '/#home' },
        { label: t.footerReviews, href: '/#reviews' },
        { label: t.nav?.[4] ?? 'Contact', href: '#contact' },
      ],
    },
  ];

  return (
    <footer id="contact" className="bg-brandDark px-4 py-12 text-white sm:px-8 lg:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-end">
          <div>
            <p className="text-xs font-black uppercase tracking-[0.2em] text-brandBlue">{t.contactEyebrow}</p>
            <h2 className="mt-3 font-display text-4xl font-black leading-none sm:text-5xl">{t.contactTitleFooter}</h2>
            <p className="mt-4 max-w-2xl text-sm font-semibold leading-7 text-white/70">{t.contactTextFooter}</p>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="rounded-xl border border-white/10 bg-white/5 p-4">
              <p className="flex items-center gap-2 text-sm font-black text-white">
                <ShieldCheck className="h-4 w-4 text-brandBlue" />
                {t.fastestResponse}
              </p>
              <p className="mt-2 text-xs font-semibold leading-5 text-white/60">{getLocalized(contactDetails.serviceHours, language)}</p>
            </div>
            <div className="rounded-xl border border-white/10 bg-white/5 p-4">
              <p className="flex items-center gap-2 text-sm font-black text-white">
                <Building2 className="h-4 w-4 text-brandBlue" />
                {t.operatingAreas}
              </p>
              <p className="mt-2 text-xs font-semibold leading-5 text-white/60">{contactDetails.serviceAreas.join(', ')}</p>
            </div>
          </div>
        </div>

        <div className="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {contactCards.map((card) => {
            const Icon = card.icon;

            return (
              <a
                key={card.title}
                href={card.href}
                target={card.external ? '_blank' : undefined}
                rel={card.external ? 'noreferrer' : undefined}
                className={`group rounded-2xl border p-5 shadow-soft transition duration-300 hover:-translate-y-1 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue ${
                  card.primary
                    ? 'border-[#25D366]/40 bg-[#25D366] text-white hover:bg-[#1fb457] hover:shadow-[#25D366]/20'
                    : 'border-brandLine bg-white text-brandDark hover:border-brandBlue hover:bg-brandSoft'
                }`}
              >
                <div className="flex items-start justify-between gap-4">
                  <div className={`grid h-10 w-10 place-items-center rounded-xl ${card.primary ? 'bg-white/20' : 'bg-brandBlue text-white'}`}>
                    <Icon className="h-4 w-4" />
                  </div>
                  <ChevronRight className={`h-4 w-4 transition group-hover:translate-x-0.5 ${card.primary ? 'text-white/80' : 'text-brandBlue'}`} />
                </div>
                <h3 className="mt-4 font-display text-2xl font-black leading-none">{card.title}</h3>
                <p className={`mt-2 text-sm font-semibold leading-6 ${card.primary ? 'text-white/85' : 'text-brandMuted'}`}>{card.text}</p>
                <p className={`mt-4 text-sm font-black ${card.primary ? 'text-white' : 'text-brandBlue'}`}>{card.value}</p>
              </a>
            );
          })}
        </div>

        <div className="mt-8 grid gap-6 rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6 lg:grid-cols-[1fr_auto] lg:items-center">
          <div>
            <p className="font-display text-2xl font-black">{t.tripDaySupportTitle}</p>
            <p className="mt-2 max-w-3xl text-sm font-semibold leading-6 text-white/65">{t.tripDaySupportText}</p>
            <p className="mt-3 text-xs font-bold leading-5 text-white/50">{getLocalized(contactDetails.paymentNote, language)}</p>
          </div>
          <a href={whatsappUrl} target="_blank" rel="noreferrer" className={whatsappButtonClass}>
            <MessageCircle className={iconSize} /> {t.sendToWhatsapp ?? 'WhatsApp'}
          </a>
        </div>

        <div className="mt-10 grid gap-8 border-t border-white/10 pt-8 lg:grid-cols-[1.25fr_2fr]">
          <div>
            <img src={logoUrl} alt="Tinggal Jalan" className="h-12 w-auto rounded bg-white px-3 py-2" />
            <p className="mt-5 max-w-xl text-sm font-semibold leading-7 text-white/65">{t.footerText}</p>
            <div className="mt-5 flex flex-wrap gap-2">
              {trustBadges.map((item) => (
                <span key={item} className="rounded-full border border-white/15 px-3 py-2 text-xs font-black text-white/75">
                  {item}
                </span>
              ))}
            </div>
          </div>

          <nav className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4" aria-label="Footer navigation">
            {footerColumns.map((column) => (
              <div key={column.title}>
                <h3 className="font-display text-xl font-black">{column.title}</h3>
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
        </div>

        <div className="mt-10 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs font-semibold text-white/50 sm:flex-row sm:items-center sm:justify-between">
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
