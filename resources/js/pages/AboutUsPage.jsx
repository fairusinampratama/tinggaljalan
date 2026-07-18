import { usePage } from '@inertiajs/react';
import {
  Building2,
  CheckCircle2,
  Compass,
  ExternalLink,
  Headphones,
  Heart,
  Mail,
  MapPin,
  MessageCircle,
  Search,
  ShieldCheck,
  Users,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { Seo } from '../components/seo/Seo';
import { ResponsiveImage } from '../components/ui/ResponsiveImage';
import { secondaryButtonClass, whatsappButtonClass } from '../components/ui/styles';
import { useBooking } from '../context/BookingContext';
import { getLocalized } from '../utils/localization';

const icons = {
  compass: Compass,
  'map-pin': MapPin,
  users: Users,
  heart: Heart,
  'circle-check': CheckCircle2,
  'message-circle': MessageCircle,
  search: Search,
  headphones: Headphones,
};

function SectionHeading({ content, language, centered = false }) {
  return (
    <div className={`mb-6 max-w-3xl lg:mb-8 ${centered ? 'mx-auto text-center' : ''}`}>
      {getLocalized(content?.eyebrow, language) ? <p className="public-eyebrow text-secondary">{getLocalized(content.eyebrow, language)}</p> : null}
      {getLocalized(content?.title, language) ? <h2 className="public-heading-section mt-3 text-primary">{getLocalized(content.title, language)}</h2> : null}
      {getLocalized(content?.intro, language) ? <p className="public-copy mt-3">{getLocalized(content.intro, language)}</p> : null}
    </div>
  );
}

function SmartLink({ href, className, children }) {
  if (!href) return null;
  if (href.startsWith('/')) return <Link to={href} className={className}>{children}</Link>;
  return <a href={href} className={className} target="_blank" rel="noreferrer">{children}</a>;
}

function VerificationCard({ icon: Icon, label, value, href }) {
  const baseClassName = 'flex min-h-14 items-start gap-3 rounded-xl border border-line bg-white p-3 lg:block lg:p-4';
  const className = href
    ? `group ${baseClassName} transition hover:border-secondary`
    : baseClassName;
  const content = (
    <>
      <Icon className="mt-0.5 h-5 w-5 shrink-0 text-secondary lg:mt-0" aria-hidden="true" />
      <div className="min-w-0 flex-1">
        <p className="text-[11px] font-bold uppercase tracking-[0.07em] text-muted lg:mt-3">{label}</p>
        <div className={`mt-1 break-words text-sm font-bold leading-6 text-primary ${href ? 'group-hover:text-secondary' : ''}`}>{value}</div>
      </div>
    </>
  );

  if (!href) return <div className={className}>{content}</div>;

  return (
    <a href={href} className={className} target={href.startsWith('http') ? '_blank' : undefined} rel={href.startsWith('http') ? 'noreferrer' : undefined}>
      {content}
    </a>
  );
}

function getInitials(name = '') {
  return name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('');
}

function localizedLanguageName(value, t) {
  const normalized = String(value ?? '').trim().toLowerCase();
  const labels = {
    indonesian: t.aboutLanguageIndonesian,
    indonesia: t.aboutLanguageIndonesian,
    english: t.aboutLanguageEnglish,
    chinese: t.aboutLanguageChinese,
    mandarin: t.aboutLanguageChinese,
  };

  return labels[normalized] || value;
}

function Portrait({ member, language, t, className }) {
  if (member.portrait) {
    return (
      <ResponsiveImage
        src={member.portrait}
        alt={getLocalized(member.portraitAlt, language) || `${member.name} — ${t.aboutPortraitFallback}`}
        className={className}
        sizes="(min-width: 1024px) 32vw, (min-width: 640px) 50vw, 100vw"
        width={900}
        height={1100}
      />
    );
  }

  return (
    <div className={`${className} flex items-center justify-center bg-primary text-white`} role="img" aria-label={`${member.name} — ${t.aboutPortraitFallback}`}>
      <span className="font-display text-5xl">{getInitials(member.name) || 'TJ'}</span>
    </div>
  );
}

function MemberDetails({ member, language, t }) {
  return (
    <>
      <h3 className="public-heading-card text-primary">{member.name}</h3>
      <p className="mt-1 text-sm font-bold text-secondary">{getLocalized(member.role, language)}</p>
      <p className="public-copy mt-3">{getLocalized(member.biography, language)}</p>
      {member.location ? <p className="mt-4 flex items-center gap-2 text-xs font-semibold text-muted"><MapPin className="h-3.5 w-3.5 text-secondary" aria-hidden="true" />{member.location}</p> : null}
      {member.languages?.length ? (
        <div className="mt-3 flex flex-wrap gap-1.5">
          {member.languages.map((item) => <span key={item} className="rounded-full bg-subtle px-2.5 py-1 text-[10px] font-bold text-muted">{localizedLanguageName(item, t)}</span>)}
        </div>
      ) : null}
      {member.profileUrl ? (
        <a href={member.profileUrl} target="_blank" rel="noreferrer" className="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-secondary hover:text-primary">
          <ExternalLink className="h-3.5 w-3.5" aria-hidden="true" />{t.aboutProfileLink}
        </a>
      ) : null}
    </>
  );
}

function TeamCard({ member, language, t }) {
  return (
    <article className="w-[82vw] max-w-[20rem] shrink-0 snap-start overflow-hidden rounded-2xl border border-line bg-white shadow-soft md:w-auto md:max-w-none">
      <Portrait member={member} language={language} t={t} className="aspect-[4/5] w-full object-cover" />
      <div className="p-5"><MemberDetails member={member} language={language} t={t} /></div>
    </article>
  );
}

export function AboutUsPage() {
  const { props } = usePage();
  const { language, publicData, whatsappUrl, t } = useBooking();
  const page = props.aboutPage ?? {};
  const visibility = page.sectionVisibility ?? {};
  const hero = page.hero ?? {};
  const story = page.story ?? {};
  const values = page.values ?? {};
  const teamSection = page.team ?? {};
  const milestonesSection = page.milestones ?? {};
  const workflow = page.workflow ?? {};
  const profile = page.profile ?? {};
  const cta = page.cta ?? {};
  const teamMembers = props.teamMembers ?? [];
  const milestones = props.milestones ?? [];
  const platformLinks = props.platformLinks ?? [];
  const contact = publicData.site?.contactDetails ?? {};
  const serviceAreas = publicData.site?.serviceAreas ?? [];
  const secondaryUrl = cta.secondary_url === 'whatsapp' ? whatsappUrl : cta.secondary_url;
  const featuredMember = teamMembers.find((member) => member.isFeatured && member.category !== 'field');
  const internalMembers = teamMembers.filter((member) => member.category !== 'field' && member.id !== featuredMember?.id);
  const fieldPartners = teamMembers.filter((member) => member.category === 'field');
  const teamCategories = ['leadership', 'booking', 'operations'];

  const verificationItems = [
    contact.address ? { icon: MapPin, label: t.contactBaseTitle, value: contact.address, href: contact.map_url || null } : null,
    contact.whatsapp ? { icon: MessageCircle, label: t.aboutDirectContact, value: contact.whatsapp, href: whatsappUrl } : null,
    contact.email ? { icon: Mail, label: t.aboutEmail, value: contact.email, href: `mailto:${contact.email}` } : null,
    serviceAreas.length ? { icon: Compass, label: t.aboutOperatingAreas, value: serviceAreas.join(' · ') } : null,
  ].filter(Boolean);
  const legalDetails = [
    profile.show_legal_name && profile.legal_name ? { icon: Building2, label: getLocalized(profile.legal_name_label, language) || t.aboutLegalName, value: profile.legal_name } : null,
    profile.show_founding_year && profile.founding_year ? { icon: ShieldCheck, label: getLocalized(profile.founding_year_label, language) || t.aboutFounded, value: profile.founding_year } : null,
    profile.show_registration && profile.registration ? { icon: CheckCircle2, label: getLocalized(profile.registration_label, language) || t.aboutRegistration, value: profile.registration } : null,
  ].filter(Boolean);
  const profileFacts = [
    ...legalDetails.map(({ label, value }) => ({ label, value })),
    contact.address ? { label: t.aboutOffice, value: contact.address } : null,
    serviceAreas.length ? { label: t.aboutOperatingAreas, value: serviceAreas.join(', ') } : null,
    { label: t.aboutTravelStyle, value: t.aboutTravelStyleValue },
    { label: t.aboutMainServices, value: t.aboutMainServicesValue },
  ].filter((item) => item?.value);
  const profileActions = [
    contact.map_url ? { icon: MapPin, label: t.aboutOpenMap, href: contact.map_url, className: secondaryButtonClass } : null,
    contact.email ? { icon: Mail, label: t.aboutEmailUs, href: `mailto:${contact.email}`, className: secondaryButtonClass } : null,
    contact.whatsapp ? { icon: MessageCircle, label: t.aboutChatWhatsapp, href: whatsappUrl, className: whatsappButtonClass } : null,
  ].filter(Boolean);

  return (
    <>
      <Seo {...(props.seo ?? {})} language={language} />
      <article className="about-page pt-16 sm:pt-[72px]">
        <section id="about-hero" className="overflow-hidden bg-primary text-white">
          <div className="public-container grid lg:min-h-[620px] lg:grid-cols-[1.02fr_0.98fr]">
            <div className="order-2 flex flex-col justify-center px-4 py-10 sm:px-8 sm:py-12 lg:order-1 lg:px-10 lg:py-20">
              {getLocalized(hero.eyebrow, language) ? <p className="public-eyebrow text-accent">{getLocalized(hero.eyebrow, language)}</p> : null}
              <h1 className="public-heading-hero mt-4 text-white">{getLocalized(hero.title, language)}</h1>
              <p className="mt-5 max-w-2xl text-base font-medium leading-7 text-white/75 sm:mt-6 sm:text-lg sm:leading-8">{getLocalized(hero.intro, language)}</p>
              {hero.facts?.length ? (
                <div className="mt-7 divide-y divide-white/10 border-y border-white/10 lg:mt-9 lg:grid lg:grid-cols-3 lg:gap-3 lg:divide-y-0 lg:border-y-0">
                  {hero.facts.map((fact, index) => {
                    const Icon = icons[fact.icon] ?? Compass;
                    return (
                      <div key={`${getLocalized(fact.label, language)}-${index}`} className="flex min-h-12 items-center gap-3 py-3 lg:block lg:rounded-xl lg:border lg:border-white/10 lg:bg-white/5 lg:p-4">
                        <Icon className="h-4 w-4 shrink-0 text-accent" aria-hidden="true" />
                        <div className="flex min-w-0 flex-1 items-center justify-between gap-3 lg:mt-3 lg:block">
                          <p className="text-[10px] font-bold uppercase tracking-[0.08em] text-white/50 lg:text-[11px]">{getLocalized(fact.label, language)}</p>
                          <p className="max-w-[58%] break-words text-right text-sm font-bold text-white lg:mt-1 lg:max-w-none lg:text-left">{getLocalized(fact.value, language)}</p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : null}
            </div>
            <div className="relative order-1 min-h-[280px] sm:min-h-[320px] lg:order-2 lg:min-h-full">
              {hero.image ? <ResponsiveImage src={hero.image} alt={getLocalized(hero.image_alt, language)} className="absolute inset-0 h-full w-full object-cover" sizes="(min-width: 1024px) 50vw, 100vw" loading="eager" fetchPriority="high" width={1200} height={900} /> : <div className="absolute inset-0 flex items-center justify-center bg-secondary/20"><Users className="h-20 w-20 text-white/35" aria-hidden="true" /></div>}
              <div className="absolute inset-0 bg-gradient-to-t from-primary/45 via-transparent to-transparent lg:bg-gradient-to-r lg:from-primary/30" />
            </div>
          </div>
        </section>

        {verificationItems.length ? (
          <section aria-labelledby="about-verification-title" className="border-b border-line bg-subtle px-4 py-10 sm:px-8 lg:px-10">
            <div className="public-container">
              <div className="grid gap-7 lg:grid-cols-[0.65fr_1.35fr] lg:items-end">
                <div>
                  <p className="public-eyebrow text-secondary">{t.aboutVerificationEyebrow}</p>
                  <h2 id="about-verification-title" className="mt-2 font-display text-3xl leading-tight text-primary">{t.aboutVerificationTitle}</h2>
                </div>
                {verificationItems.length ? <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{verificationItems.map((item) => <VerificationCard key={`${item.label}-${item.value}`} {...item} />)}</div> : null}
              </div>
            </div>
          </section>
        ) : null}

        {visibility.team !== false && teamMembers.length ? (
          <section className="public-section bg-subtle">
            <div className="public-container">
              <SectionHeading content={teamSection} language={language} />
              {featuredMember ? (
                <article className="mb-8 overflow-hidden rounded-2xl border border-line bg-white shadow-soft lg:mb-10 lg:grid lg:grid-cols-[0.72fr_1.28fr]">
                  <Portrait member={featuredMember} language={language} t={t} className="aspect-[16/10] w-full object-cover lg:aspect-[4/5] lg:h-full lg:min-h-[360px]" />
                  <div className="flex flex-col justify-center p-5 sm:p-7 lg:p-9">
                    <p className="mb-3 text-xs font-bold uppercase tracking-[0.08em] text-secondary lg:mb-4">{t.aboutFeaturedTeam}</p>
                    <MemberDetails member={featuredMember} language={language} t={t} />
                  </div>
                </article>
              ) : null}
              <div className="space-y-8 lg:space-y-10">
                {teamCategories.map((category) => {
                  const members = internalMembers.filter((member) => member.category === category);
                  if (!members.length) return null;
                  return (
                    <section key={category} aria-labelledby={`team-${category}`}>
                      <h3 id={`team-${category}`} className="mb-4 text-sm font-bold uppercase tracking-[0.08em] text-secondary">{getLocalized(teamSection.category_labels?.[category], language)}</h3>
                      <div className="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary sm:-mx-8 sm:px-8 md:mx-0 md:grid md:grid-cols-2 md:gap-5 md:overflow-visible md:px-0 md:pb-0 lg:grid-cols-3" role="region" aria-label={getLocalized(teamSection.category_labels?.[category], language)} tabIndex={members.length > 1 ? 0 : undefined}>{members.map((member) => <TeamCard key={member.id} member={member} language={language} t={t} />)}</div>
                    </section>
                  );
                })}
              </div>
              {fieldPartners.length ? (
                <section className="mt-10 rounded-2xl bg-primary p-5 text-white sm:p-6 lg:mt-12 lg:p-8" aria-labelledby="partner-network-title">
                  <div className="grid gap-6 lg:grid-cols-[0.65fr_1.35fr] lg:gap-7">
                    <div>
                      <p className="public-eyebrow text-accent">{getLocalized(teamSection.category_labels?.field, language)}</p>
                      <h3 id="partner-network-title" className="mt-3 font-display text-2xl lg:text-3xl">{t.aboutPartnerNetwork}</h3>
                      <p className="mt-3 text-sm font-medium leading-7 text-white/65">{t.aboutPartnerNetworkText}</p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                      {fieldPartners.map((member) => (
                        <article key={member.id} className="rounded-xl border border-white/10 bg-white/5 p-4 lg:p-5">
                          <h4 className="font-display text-xl text-white">{member.name}</h4>
                          <p className="mt-1 text-sm font-bold text-accent">{getLocalized(member.role, language)}</p>
                          <p className="mt-3 text-sm font-medium leading-6 text-white/65">{getLocalized(member.biography, language)}</p>
                        </article>
                      ))}
                    </div>
                  </div>
                </section>
              ) : null}
            </div>
          </section>
        ) : null}

        {visibility.story !== false && (getLocalized(story.title, language) || getLocalized(story.body, language)) ? (
          <section className="public-section bg-white">
            <div className={`public-container grid gap-6 lg:gap-8 ${story.image ? 'lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-14' : ''}`}>
              {story.image ? <ResponsiveImage src={story.image} alt={getLocalized(story.image_alt, language)} className="aspect-[16/10] w-full rounded-2xl object-cover shadow-soft lg:aspect-[3/2]" sizes="(min-width: 1024px) 45vw, 100vw" width={1200} height={800} /> : null}
              <div className={story.image ? '' : 'max-w-4xl'}>
                <p className="public-eyebrow text-secondary">{getLocalized(story.eyebrow, language)}</p>
                <h2 className="public-heading-section mt-3 text-primary">{getLocalized(story.title, language)}</h2>
                <div className="mt-4 whitespace-pre-line text-sm font-medium leading-7 text-muted sm:text-base sm:leading-8 lg:mt-5">{getLocalized(story.body, language)}</div>
                {getLocalized(story.quote, language) ? (
                  <figure className="mt-6 rounded-r-xl border-l-2 border-accent bg-subtle px-4 py-4 lg:mt-7 lg:px-5 lg:py-5">
                    <blockquote className="font-display text-xl leading-snug text-primary lg:text-2xl">“{getLocalized(story.quote, language)}”</blockquote>
                    {story.quote_author ? <figcaption className="mt-3 text-xs font-bold uppercase tracking-[0.08em] text-muted">— {story.quote_author}</figcaption> : null}
                  </figure>
                ) : null}
              </div>
            </div>
          </section>
        ) : null}

        {visibility.workflow !== false && workflow.steps?.length ? (
          <section className="public-section bg-primary text-white">
            <div className="public-container">
              <div className="mb-7 max-w-3xl lg:mb-9">
                <p className="public-eyebrow text-accent">{getLocalized(workflow.eyebrow, language)}</p>
                <h2 className="public-heading-section mt-3 text-white">{getLocalized(workflow.title, language)}</h2>
                <p className="mt-3 text-sm font-medium leading-7 text-white/65">{getLocalized(workflow.intro, language)}</p>
              </div>
              <ol className="relative grid gap-0 lg:grid-cols-5 lg:gap-3 lg:pt-12">
                <span aria-hidden="true" className="absolute left-[10%] right-[10%] top-6 hidden h-px bg-white/20 lg:block" />
                {workflow.steps.map((step, index) => {
                  const Icon = icons[step.icon] ?? Compass;
                  return (
                    <li key={`${getLocalized(step.title, language)}-${index}`} className="relative grid grid-cols-[2.5rem_1fr] gap-4 py-3 first:pt-0 last:pb-0 lg:block lg:rounded-xl lg:border lg:border-white/10 lg:bg-white/5 lg:p-5">
                      <div className="relative lg:hidden">
                        <span className="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-primary text-accent">
                          <Icon className="h-4 w-4" aria-hidden="true" />
                        </span>
                        {index < workflow.steps.length - 1 ? <span aria-hidden="true" className="absolute -bottom-3 left-5 top-10 w-px bg-white/15" /> : null}
                      </div>
                      <span className="absolute -top-[2.75rem] left-1/2 z-10 hidden h-10 w-10 -translate-x-1/2 items-center justify-center rounded-full border border-accent/60 bg-primary text-accent shadow-[0_0_0_6px_var(--color-primary)] lg:flex" aria-hidden="true">
                        <Icon className="h-5 w-5" />
                      </span>
                      <div className="min-w-0 pb-2 lg:pb-0">
                        <span className="text-[10px] font-bold uppercase tracking-[0.08em] text-white/35 lg:text-xs">{String(index + 1).padStart(2, '0')}</span>
                        <h3 className="mt-1 font-display text-xl leading-tight text-white lg:mt-4">{getLocalized(step.title, language)}</h3>
                        <p className="mt-2 text-sm font-medium leading-6 text-white/60 lg:mt-3">{getLocalized(step.text, language)}</p>
                      </div>
                    </li>
                  );
                })}
              </ol>
            </div>
          </section>
        ) : null}

        {visibility.milestones !== false && milestones.length ? (
          <section className="public-section overflow-hidden bg-white">
            <div className="public-container">
              <SectionHeading content={milestonesSection} language={language} />
              <div className="relative">
                <div aria-hidden="true" className="absolute bottom-0 left-4 top-0 w-px bg-line lg:bottom-auto lg:left-0 lg:right-0 lg:top-5 lg:h-px lg:w-full" />
                <ol className="relative grid gap-5 lg:grid-flow-col lg:auto-cols-fr lg:gap-5">
                  {milestones.map((milestone, index) => (
                    <li key={milestone.id} className="relative pl-9 lg:pl-0 lg:pt-12">
                      <span aria-hidden="true" className="absolute left-[9px] top-3 flex h-4 w-4 rounded-full border-4 border-white bg-secondary shadow lg:left-1/2 lg:top-[13px] lg:-translate-x-1/2" />
                      <article className="rounded-xl border border-line bg-surface p-4 lg:p-5">
                        {milestone.image ? <ResponsiveImage src={milestone.image} alt={getLocalized(milestone.imageAlt, language)} className="mb-4 aspect-video w-full rounded-lg object-cover lg:mb-5 lg:aspect-[3/2]" sizes="(min-width: 1024px) 25vw, 100vw" width={900} height={600} /> : null}
                        <div className="flex items-center justify-between gap-4">
                          <span className="font-display text-2xl text-secondary">{getLocalized(milestone.period, language)}</span>
                          <span className="text-xs font-bold text-accent">{String(index + 1).padStart(2, '0')}</span>
                        </div>
                        <h3 className="public-heading-card mt-3 text-primary lg:mt-4">{getLocalized(milestone.title, language)}</h3>
                        <p className="public-copy mt-2 lg:mt-3">{getLocalized(milestone.description, language)}</p>
                      </article>
                    </li>
                  ))}
                </ol>
              </div>
            </div>
          </section>
        ) : null}

        {visibility.values !== false && values.items?.length ? (
          <section className="public-section bg-subtle">
            <div className="public-container">
              <SectionHeading content={values} language={language} centered />
              <div className="grid gap-4 md:grid-cols-3">
                {values.items.map((item, index) => {
                  const Icon = icons[item.icon] ?? Compass;
                  return (
                    <article key={`${getLocalized(item.title, language)}-${index}`} className="grid grid-cols-[2.5rem_1fr] gap-4 rounded-xl border border-line bg-white p-4 md:block md:p-5">
                      <div className="flex h-10 w-10 items-center justify-center rounded-full bg-secondary/10 text-secondary"><Icon className="h-5 w-5" aria-hidden="true" /></div>
                      <div>
                        <h3 className="public-heading-card text-primary md:mt-4">{getLocalized(item.title, language)}</h3>
                        <p className="public-copy mt-2 md:mt-3">{getLocalized(item.text, language)}</p>
                      </div>
                    </article>
                  );
                })}
              </div>
            </div>
          </section>
        ) : null}

        {visibility.profile !== false && (getLocalized(profile.operating_description, language) || profileFacts.length) ? (
          <section className="public-section bg-white">
            <div className="public-container">
              <div className="grid gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:items-start lg:gap-14">
                <div className="lg:sticky lg:top-28">
                  <SectionHeading content={profile} language={language} />
                  {getLocalized(profile.operating_description, language) ? (
                    <p className="public-copy max-w-xl border-l-2 border-accent pl-4">{getLocalized(profile.operating_description, language)}</p>
                  ) : null}
                </div>
                <div className="overflow-hidden rounded-2xl border border-line bg-surface">
                  {profileFacts.length ? (
                    <dl className="divide-y divide-line">
                      {profileFacts.map((fact) => (
                        <div key={fact.label} className="grid gap-1 px-5 py-4 sm:grid-cols-[10rem_1fr] sm:gap-5 sm:px-6">
                          <dt className="text-xs font-bold uppercase tracking-[0.07em] text-muted">{fact.label}</dt>
                          <dd className="break-words text-sm font-bold leading-6 text-primary">{fact.value}</dd>
                        </div>
                      ))}
                    </dl>
                  ) : null}
                  {profileActions.length ? (
                    <div className="flex flex-col gap-3 border-t border-line bg-subtle p-5 sm:flex-row sm:flex-wrap sm:p-6">
                      {profileActions.map(({ icon: Icon, label, href, className }) => (
                        <SmartLink key={label} href={href} className={`${className} w-full sm:w-auto`}>
                          <Icon className="h-4 w-4" aria-hidden="true" />{label}
                        </SmartLink>
                      ))}
                    </div>
                  ) : null}
                  {platformLinks.length ? (
                    <div className="border-t border-line px-5 py-5 sm:px-6">
                      <p className="text-xs font-bold uppercase tracking-[0.07em] text-muted">{t.aboutAvailableOn}</p>
                      <div className="mt-3 flex flex-wrap gap-2">
                        {platformLinks.map((link) => (
                          <a key={link.name} href={link.url} target="_blank" rel="noreferrer" className="inline-flex min-h-10 items-center gap-2 rounded-full border border-line bg-white px-4 py-2 text-sm font-bold text-primary transition hover:border-secondary hover:text-secondary">
                            {link.logo ? <img src={link.logo} alt="" className="h-5 w-5 object-contain" loading="lazy" width="20" height="20" /> : null}
                            {link.name}<ExternalLink className="h-3.5 w-3.5" aria-hidden="true" />
                          </a>
                        ))}
                      </div>
                    </div>
                  ) : null}
                </div>
              </div>
            </div>
          </section>
        ) : null}

        {visibility.cta !== false && getLocalized(cta.title, language) ? (
          <section className="public-section bg-subtle">
            <div className="public-container rounded-2xl bg-primary px-5 py-9 text-center text-white shadow-soft sm:px-10 sm:py-14">
              <h2 className="mx-auto max-w-3xl font-display text-3xl font-normal leading-tight sm:text-4xl">{getLocalized(cta.title, language)}</h2>
              <p className="mx-auto mt-4 max-w-2xl text-sm font-medium leading-7 text-white/65">{getLocalized(cta.text, language)}</p>
              <div className="mt-6 flex flex-col justify-center gap-3 sm:mt-7 sm:flex-row">
                <SmartLink href={cta.primary_url} className={`${secondaryButtonClass} w-full sm:w-auto`}><Compass className="h-4 w-4" aria-hidden="true" />{getLocalized(cta.primary_label, language)}</SmartLink>
                <SmartLink href={secondaryUrl} className={`${whatsappButtonClass} w-full sm:w-auto`}><MessageCircle className="h-4 w-4" aria-hidden="true" />{getLocalized(cta.secondary_label, language)}</SmartLink>
              </div>
            </div>
          </section>
        ) : null}
      </article>
    </>
  );
}
