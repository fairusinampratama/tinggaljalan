import { useState } from 'react';
import {
  Award,
  CalendarDays,
  CheckCircle,
  ChevronDown,
  ChevronRight,
  Clock,
  Compass,
  Mail,
  MapPin,
  Menu,
  MessageCircle,
  Mountain,
  Phone,
  Search,
  Send,
  ShieldCheck,
  Sparkles,
  Star,
  X,
  Users,
} from 'lucide-react';

const whatsappUrl = 'https://wa.me/62811388330';

const navItems = [
  { label: 'About Us', href: '#about-us' },
  { label: 'Destination', href: '#destination', children: 'destinations' },
  { label: 'Packages', href: '#packages', children: 'packages' },
  { label: 'Reviews', href: '#reviews' },
  { label: 'Activities', href: '#activities' },
];

const destinationOptions = ['Bromo', 'Jogja', 'Tumpak Sewu', 'Medan'];
const tripTypeOptions = ['Private Trip', 'Open Trip', 'Custom Tour', 'Corporate Gathering'];
const paxOptions = ['1 Orang', '2 Orang', '3-5 Orang', '6+ Orang'];

const buttonLift =
  'transition duration-300 hover:-translate-y-0.5 hover:shadow-2xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sunset';
const cardLift =
  'transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-forest/12';
const fieldFocus =
  'transition hover:border-clay/50 hover:bg-white focus:border-clay focus:ring-4 focus:ring-clay/10';

const destinations = [
  {
    name: 'Bromo',
    location: 'Jawa Timur',
    image: '/images/destination-bromo.jpg',
    copy: 'Sunrise, lautan pasir, dan jeep adventure dalam satu perjalanan.',
    highlight: 'Sunrise + Jeep',
  },
  {
    name: 'Jogja',
    location: 'DI Yogyakarta',
    image: '/images/destination-jogja.jpg',
    copy: 'Candi, budaya, kuliner, dan city tour yang hangat untuk semua usia.',
    highlight: 'Culture + Culinary',
  },
  {
    name: 'Tumpak Sewu',
    location: 'Lumajang',
    image: '/images/destination-tumpak-sewu.jpg',
    copy: 'Air terjun megah, jalur alam, dan spot foto yang dramatis.',
    highlight: 'Waterfall + Adventure',
  },
  {
    name: 'Medan',
    location: 'Sumatera Utara',
    image: '/images/destination-medan.jpg',
    copy: 'Danau, kuliner, dan perjalanan khas Sumatera yang berkesan.',
    highlight: 'Lake Toba + Culinary',
  },
];

const packages = [
  {
    title: 'Bromo Sunrise Private Trip',
    tag: 'Best Seller',
    image: '/images/package-bromo-jeep.jpg',
    duration: '1 Hari',
    route: 'Malang/Surabaya - Penanjakan - Kawah Bromo',
    price: 'Mulai 350K/pax',
    bestFor: 'Couple, family, dan first timer',
    includes: ['Jeep 4x4', 'Sunrise point', 'Driver lokal', 'Dokumentasi basic'],
  },
  {
    title: 'Bromo + Tumpak Sewu 2D1N',
    tag: 'Recommended',
    image: '/images/destination-tumpak-sewu.jpg',
    duration: '2D1N',
    route: 'Bromo Sunrise - Tumpak Sewu - Panorama Spot',
    price: 'Mulai 875K/pax',
    bestFor: 'Adventure trip dan konten visual',
    includes: ['Transport nyaman', 'Guide lokal', 'Penginapan pilihan', 'Itinerary rapi'],
  },
  {
    title: 'Jogja Heritage & Culinary',
    tag: 'Culture Trip',
    image: '/images/destination-jogja.jpg',
    duration: '2D1N',
    route: 'Candi - City Tour - Kuliner Lokal',
    price: 'Mulai 650K/pax',
    bestFor: 'Keluarga, komunitas, dan private group',
    includes: ['City tour', 'Candi pilihan', 'Kuliner lokal', 'Planner perjalanan'],
  },
  {
    title: 'Medan / Lake Toba Escape',
    tag: 'Private Trip',
    image: '/images/destination-medan.jpg',
    duration: '3D2N',
    route: 'Medan - Danau Toba - Kuliner Lokal',
    price: 'Mulai 1.450K/pax',
    bestFor: 'Trip santai dan eksplor Sumatera',
    includes: ['Transport', 'Hotel pilihan', 'Local experience', 'Trip assistant'],
  },
];

const trustItems = [
  { title: 'Google Reviews', value: '5.0 / 5.0', icon: Star },
  { title: 'NPS Customer', value: '9.9 / 10', icon: Award },
  { title: 'Certified Planner', value: 'Standar resmi', icon: ShieldCheck },
  { title: 'Local Guide', value: 'Tim Malang', icon: MapPin },
];

const activities = [
  { title: 'Sunrise Hunting', icon: Mountain, text: 'Kejar pagi terbaik dari view point pilihan.' },
  { title: 'Jeep Adventure', icon: Compass, text: 'Rute pasir, savana, dan kawah dengan driver lokal.' },
  { title: 'Culture Trip', icon: Sparkles, text: 'Cerita kota, candi, museum, dan kuliner autentik.' },
  { title: 'Waterfall Trail', icon: MapPin, text: 'Jalur alam menuju air terjun dan lembah hijau.' },
];

const galleryImages = [
  { image: '/images/hero-bromo.jpg', title: 'Sunrise Bromo', location: 'Jawa Timur' },
  { image: '/images/package-bromo-jeep.jpg', title: 'Jeep Adventure', location: 'Bromo' },
  { image: '/images/destination-jogja.jpg', title: 'Borobudur Heritage', location: 'Jogja' },
  { image: '/images/destination-tumpak-sewu.jpg', title: 'Tumpak Sewu Waterfall', location: 'Lumajang' },
  { image: '/images/destination-medan.jpg', title: 'Lake Toba Escape', location: 'Sumatera Utara' },
  { image: '/images/gallery-rice-terrace.jpg', title: 'Rural View', location: 'Indonesia' },
  { image: '/images/gallery-indonesia-green.jpg', title: 'Green Landscape', location: 'Indonesia' },
  { image: '/images/destination-bromo.jpg', title: 'Bromo Viewpoint', location: 'Jawa Timur' },
];

const testimonials = [
  {
    name: 'Joe',
    country: 'UK',
    text: 'Trip sunrise berjalan mulus dari awal sampai selesai. Guide ramah, on time, dan sangat membantu.',
  },
  {
    name: 'Ryo',
    country: 'Japan',
    text: 'Tinggal Jalan membuat perjalanan Bromo terasa personal. Semua detail sudah disiapkan dengan baik.',
  },
  {
    name: 'Sarah',
    country: 'Australia',
    text: 'Tim profesional, jeep nyaman, dan hospitality bagus. Sangat cocok untuk traveler internasional.',
  },
];

const footerLinks = [
  { label: 'About Us', href: '#about-us' },
  { label: 'Destination', href: '#destination' },
  { label: 'Packages', href: '#packages' },
  { label: 'Reviews', href: '#reviews' },
  { label: 'Gallery', href: '#activities' },
];

const footerDestinations = ['Bromo', 'Jogja', 'Tumpak Sewu', 'Medan / Lake Toba'];

const contactItems = [
  {
    icon: MapPin,
    label: 'Head Office',
    value: 'Jalan Danau Tondano Dalam A2 D28 Sawojajar, Kota Malang',
  },
  {
    icon: Phone,
    label: 'WhatsApp',
    value: '081547555774 / 0811388330',
  },
  {
    icon: Mail,
    label: 'Customer Service',
    value: 'hai@tinggaljalan.co.id',
  },
  {
    icon: Mail,
    label: 'Kerjasama',
    value: 'atok@tinggaljalan.co.id',
  },
];

function getDropdownItems(type) {
  if (type === 'destinations') {
    return destinations.map((item) => ({
      title: item.name,
      meta: item.highlight,
      href: '#destination',
      image: item.image,
    }));
  }

  if (type === 'packages') {
    return packages.map((item) => ({
      title: item.title,
      meta: `${item.duration} - ${item.price}`,
      href: '#packages',
      image: item.image,
    }));
  }

  return [];
}

function SectionHeader({ eyebrow, title, children, light = false }) {
  return (
    <div className="mx-auto mb-10 max-w-3xl text-center">
      <p className="mb-3 text-sm font-bold uppercase tracking-[0.24em] text-clay">{eyebrow}</p>
      <h2 className={`font-display text-3xl font-extrabold sm:text-4xl ${light ? 'text-white' : 'text-forest'}`}>{title}</h2>
      {children ? <p className={`mt-4 text-base leading-7 ${light ? 'text-white/72' : 'text-forest/70'}`}>{children}</p> : null}
    </div>
  );
}

function SelectBox({ id, label, value, options, icon: Icon, openSelect, setOpenSelect, onChange, variant = 'compact' }) {
  const isOpen = openSelect === id;
  const isCompact = variant === 'compact';

  return (
    <div className="relative">
      <button
        type="button"
        className={`w-full rounded-2xl border border-forest/10 bg-white px-4 text-left outline-none ${fieldFocus} ${
          isCompact ? 'bg-sand/60 py-4' : 'py-4'
        } ${isOpen ? 'border-clay bg-white ring-4 ring-clay/10' : ''}`}
        onClick={() => setOpenSelect(isOpen ? null : id)}
        aria-expanded={isOpen}
        aria-haspopup="listbox"
      >
        <span className="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-clay">
          {Icon ? <Icon size={15} /> : null} {label}
        </span>
        <span className="flex items-center justify-between gap-3 text-base font-bold text-forest">
          {value}
          <ChevronDown size={17} className={`shrink-0 transition ${isOpen ? 'rotate-180 text-clay' : ''}`} />
        </span>
      </button>

      {isOpen ? (
        <div
          className="absolute left-0 right-0 top-[calc(100%+8px)] z-30 overflow-hidden rounded-2xl border border-forest/10 bg-white p-2 text-forest shadow-2xl shadow-forest/18"
          role="listbox"
        >
          {options.map((option) => (
            <button
              key={option}
              type="button"
              className={`flex w-full items-center justify-between rounded-xl px-3 py-3 text-left text-sm font-bold transition hover:bg-sand hover:text-clay ${
                option === value ? 'bg-sand text-clay' : 'text-forest/76'
              }`}
              onClick={() => {
                onChange(option);
                setOpenSelect(null);
              }}
              role="option"
              aria-selected={option === value}
            >
              {option}
              {option === value ? <CheckCircle size={16} /> : null}
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}

function App() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [openSelect, setOpenSelect] = useState(null);
  const [searchValues, setSearchValues] = useState({
    destination: 'Bromo',
    date: 'Pilih tanggal',
    tripType: 'Private Trip',
    pax: '1 Orang',
  });
  const [bookingValues, setBookingValues] = useState({
    date: '25 Juni 2026',
    tripType: 'Private Trip',
    destination: 'Bromo',
    pax: '2 Orang',
  });

  return (
    <main className="min-h-screen overflow-hidden bg-[#fffaf3] text-forest">
      <nav className="fixed inset-x-0 top-0 z-50 border-b border-white/25 bg-forest/80 text-white backdrop-blur-xl">
        <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
          <a href="#home" className="flex items-center gap-3">
            <span className="grid h-11 w-11 place-items-center rounded-full bg-sunset text-lg font-black text-forest">
              TJ
            </span>
            <span className="text-lg font-extrabold tracking-wide">Tinggal Jalan</span>
          </a>

          <div className="hidden items-center gap-2 text-sm font-semibold lg:flex">
            {navItems.map((item) => (
              <div key={item.label} className="group relative">
                <a
                  href={item.href}
                  className="relative inline-flex items-center gap-1 rounded-full px-4 py-3 text-white/85 transition hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sunset"
                >
                  {item.label}
                  {item.children ? <ChevronDown size={15} className="transition group-hover:rotate-180" /> : null}
                  <span className="absolute bottom-1 left-4 right-4 h-0.5 origin-left scale-x-0 rounded-full bg-sunset transition group-hover:scale-x-100" />
                </a>

                {item.children ? (
                  <div className="invisible absolute left-0 top-full w-[340px] translate-y-3 rounded-[24px] border border-white/10 bg-white p-3 text-forest opacity-0 shadow-2xl shadow-forest/20 transition duration-200 group-hover:visible group-hover:translate-y-2 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-2 group-focus-within:opacity-100">
                    {getDropdownItems(item.children).map((child) => (
                      <a
                        key={child.title}
                        href={child.href}
                        className="group/item flex items-center gap-3 rounded-2xl p-3 transition hover:bg-sand focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay"
                      >
                        <img src={child.image} alt="" className="h-14 w-16 rounded-xl object-cover" />
                        <span className="min-w-0 flex-1">
                          <span className="block truncate font-extrabold">{child.title}</span>
                          <span className="mt-0.5 block truncate text-xs font-semibold text-forest/58">{child.meta}</span>
                        </span>
                        <ChevronRight size={17} className="text-clay transition group-hover/item:translate-x-1" />
                      </a>
                    ))}
                  </div>
                ) : null}
              </div>
            ))}
          </div>

          <div className="flex items-center gap-3">
            <a
              href={whatsappUrl}
              target="_blank"
              rel="noreferrer"
              className={`hidden rounded-full bg-white px-5 py-3 text-sm font-bold text-forest shadow-lg shadow-black/10 hover:bg-sand sm:inline-flex ${buttonLift}`}
            >
              Booking via WhatsApp
            </a>
            <button
              className="grid h-11 w-11 place-items-center rounded-full border border-white/25 lg:hidden"
              aria-label={isMenuOpen ? 'Tutup menu' : 'Buka menu'}
              onClick={() => setIsMenuOpen((current) => !current)}
            >
              {isMenuOpen ? <X size={20} /> : <Menu size={20} />}
            </button>
          </div>
        </div>

        {isMenuOpen ? (
          <div className="border-t border-white/15 bg-forest px-5 py-5 shadow-2xl lg:hidden">
            <div className="mx-auto flex max-w-7xl flex-col gap-2">
              {navItems.map((item) => (
                <div key={item.label}>
                  <a
                    href={item.href}
                    className="flex items-center justify-between rounded-2xl px-4 py-3 font-bold text-white/82 transition hover:bg-white/10 hover:text-white"
                    onClick={() => setIsMenuOpen(false)}
                  >
                    {item.label}
                    {item.children ? <ChevronDown size={16} /> : null}
                  </a>
                  {item.children ? (
                    <div className="ml-4 mt-1 grid gap-1 border-l border-white/12 pl-3">
                      {getDropdownItems(item.children).map((child) => (
                        <a
                          key={child.title}
                          href={child.href}
                          className="rounded-xl px-3 py-2 text-sm font-semibold text-white/68 transition hover:bg-white/10 hover:text-white"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          {child.title}
                        </a>
                      ))}
                    </div>
                  ) : null}
                </div>
              ))}
              <a
                href={whatsappUrl}
                target="_blank"
                rel="noreferrer"
                className={`mt-2 inline-flex items-center justify-center gap-2 rounded-2xl bg-sunset px-5 py-4 font-extrabold text-forest ${buttonLift}`}
                onClick={() => setIsMenuOpen(false)}
              >
                <MessageCircle size={18} /> Booking via WhatsApp
              </a>
            </div>
          </div>
        ) : null}
      </nav>

      <section id="home" className="relative min-h-[760px] bg-forest pt-20 text-white">
        <img src="/images/hero-bromo.jpg" alt="Pemandangan Gunung Bromo" className="absolute inset-0 h-full w-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-r from-forest via-forest/70 to-forest/10" />
        <div className="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-[#fffaf3] to-transparent" />

        <div className="relative mx-auto grid max-w-7xl gap-10 px-5 py-20 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 lg:py-28">
          <div className="max-w-3xl">
            <p className="mb-5 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/12 px-4 py-2 text-sm font-semibold backdrop-blur">
              <Sparkles size={16} /> Travel Agency & Consultant
            </p>
            <h1 className="font-display text-5xl font-extrabold leading-[0.98] sm:text-6xl lg:text-7xl">
              Private Trip Bromo, Jogja, Tumpak Sewu & Medan Tanpa Ribet.
            </h1>
            <p className="mt-6 max-w-2xl text-lg leading-8 text-white/82">
              Itinerary rapi, transport nyaman, local guide berpengalaman, dan dokumentasi perjalanan yang siap
              bikin trip kamu lebih tenang dari awal sampai pulang.
            </p>
            <div className="mt-9 flex flex-col gap-3 sm:flex-row">
              <a
                href={whatsappUrl}
                target="_blank"
                rel="noreferrer"
                className={`inline-flex items-center justify-center gap-2 rounded-full bg-sunset px-7 py-4 font-extrabold text-forest shadow-2xl shadow-black/20 hover:bg-[#f0aa56] ${buttonLift}`}
              >
                Booking via WhatsApp <MessageCircle size={18} />
              </a>
              <a href="#packages" className={`group inline-flex items-center justify-center gap-2 rounded-full border border-white/30 bg-white/10 px-7 py-4 font-bold text-white backdrop-blur hover:bg-white/18 ${buttonLift}`}>
                Lihat Paket <ChevronRight size={18} className="transition group-hover:translate-x-1" />
              </a>
            </div>
            <div className="mt-7 flex flex-wrap gap-3">
              {['Google 5.0', 'NPS 9.9/10', 'Local Malang Team'].map((item) => (
                <span key={item} className="rounded-full border border-white/20 bg-white/12 px-4 py-2 text-sm font-bold text-white/86 backdrop-blur">
                  {item}
                </span>
              ))}
            </div>
          </div>

          <div className="hidden self-end lg:block">
            <div className={`ml-auto max-w-sm rounded-[28px] border border-white/20 bg-white/14 p-5 shadow-2xl backdrop-blur-xl ${cardLift}`}>
              <img src="/images/package-bromo-jeep.jpg" alt="Inspirasi paket perjalanan" className="h-52 w-full rounded-[22px] object-cover" />
              <div className="mt-5 flex items-center justify-between">
                <div>
                  <p className="text-sm text-white/68">Bromo Private</p>
                  <p className="text-2xl font-extrabold">Sunrise Trip</p>
                </div>
                <div className="flex text-sunset">
                  {[1, 2, 3, 4, 5].map((item) => (
                    <Star key={item} size={16} fill="currentColor" />
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="relative mx-auto -mb-20 max-w-6xl px-5 sm:px-8 lg:px-10">
          <div className="rounded-[30px] bg-white p-5 text-forest shadow-soft">
            <div className="grid gap-4 md:grid-cols-5">
              {[
                ['destination', 'Destination', destinationOptions, MapPin],
                ['date', 'Date', ['Pilih tanggal', 'Akhir pekan ini', 'Bulan depan'], CalendarDays],
                ['tripType', 'Trip Type', tripTypeOptions, Compass],
                ['pax', 'Pax', paxOptions, Users],
              ].map(([key, label, options, Icon]) => (
                <SelectBox
                  key={key}
                  id={`search-${key}`}
                  label={label}
                  value={searchValues[key]}
                  options={options}
                  icon={Icon}
                  openSelect={openSelect}
                  setOpenSelect={setOpenSelect}
                  onChange={(option) => setSearchValues((current) => ({ ...current, [key]: option }))}
                />
              ))}
              <button className={`inline-flex items-center justify-center gap-2 rounded-2xl bg-forest px-5 py-4 font-extrabold text-white hover:bg-ocean ${buttonLift}`}>
                <Search size={18} /> Search
              </button>
            </div>
          </div>
        </div>
      </section>

      <section id="destination" className="px-5 pb-16 pt-36 sm:px-8 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <SectionHeader eyebrow="Popular Destination" title="Destinasi favorit untuk mulai jalan">
            Pilih suasana perjalanan yang paling cocok, dari sunrise gunung sampai kota budaya.
          </SectionHeader>
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {destinations.map((item) => (
              <article key={item.name} className={`group overflow-hidden rounded-[26px] bg-white shadow-soft ${cardLift}`}>
                <div className="relative h-64 overflow-hidden">
                  <img src={item.image} alt={item.name} className="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                  <div className="absolute inset-0 bg-gradient-to-t from-forest/80 to-transparent" />
                  <div className="absolute bottom-5 left-5 right-5 text-white">
                    <p className="text-sm font-semibold text-white/75">{item.location}</p>
                    <h3 className="text-3xl font-extrabold">{item.name}</h3>
                    <p className="mt-2 inline-flex rounded-full bg-white/18 px-3 py-1 text-xs font-extrabold backdrop-blur">
                      {item.highlight}
                    </p>
                  </div>
                </div>
                <div className="p-5">
                  <p className="min-h-20 leading-7 text-forest/70">{item.copy}</p>
                  <a href="#packages" className="mt-4 inline-flex translate-y-2 items-center gap-2 font-extrabold text-clay opacity-0 transition group-hover:translate-y-0 group-hover:opacity-100">
                    Lihat trip <ChevronRight size={17} className="transition group-hover:translate-x-1" />
                  </a>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="bg-white px-5 py-16 sm:px-8 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <SectionHeader eyebrow="Trusted Travel Partner" title="Bukan cuma jalan, tapi perjalanan yang diurus serius">
            Tinggal Jalan menggabungkan tim lokal, planner berpengalaman, dan hospitality Indonesia untuk perjalanan
            yang aman, nyaman, dan mudah dikonsultasikan.
          </SectionHeader>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {trustItems.map(({ title, value, icon: Icon }) => (
              <article key={title} className={`rounded-[24px] border border-forest/10 bg-[#fffaf3] p-6 shadow-soft hover:border-clay/25 ${cardLift}`}>
                <div className="mb-6 grid h-12 w-12 place-items-center rounded-2xl bg-sunset/20 text-clay">
                  <Icon size={24} />
                </div>
                <p className="text-2xl font-black text-forest">{value}</p>
                <p className="mt-2 font-semibold text-forest/64">{title}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section id="packages" className="px-5 py-16 sm:px-8 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <SectionHeader eyebrow="Package Highlight" title="Paket perjalanan siap berangkat">
            Pilih paket berdasarkan durasi, rute, dan gaya perjalanan. Detailnya dibuat jelas supaya mudah dibandingkan.
          </SectionHeader>
          <div className="grid gap-7 md:grid-cols-2 xl:grid-cols-4">
            {packages.map((item) => (
              <article key={item.title} className={`group overflow-hidden rounded-[28px] border border-forest/10 bg-[#fffaf3] shadow-soft hover:border-clay/35 ${cardLift}`}>
                <div className="h-48 overflow-hidden">
                  <img src={item.image} alt={item.title} className="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                </div>
                <div className="p-6">
                  <span className="rounded-full bg-clay/10 px-3 py-1 text-xs font-extrabold uppercase tracking-[0.16em] text-clay">
                    {item.tag}
                  </span>
                  <h3 className="mt-4 text-2xl font-extrabold">{item.title}</h3>
                  <div className="mt-4 space-y-3 text-sm font-semibold text-forest/70">
                    <p className="flex items-start gap-3">
                      <Clock className="mt-0.5 shrink-0 text-clay" size={17} /> {item.duration}
                    </p>
                    <p className="flex items-start gap-3">
                      <MapPin className="mt-0.5 shrink-0 text-clay" size={17} /> {item.route}
                    </p>
                    <p className="flex items-start gap-3">
                      <Users className="mt-0.5 shrink-0 text-clay" size={17} /> {item.bestFor}
                    </p>
                  </div>
                  <p className="mt-5 text-xl font-black text-ocean">{item.price}</p>
                  <ul className="mt-5 space-y-3 text-sm font-semibold text-forest/70">
                    {item.includes.map((detail) => (
                      <li key={detail} className="flex items-center gap-3">
                        <CheckCircle className="shrink-0 text-sunset" size={16} /> {detail}
                      </li>
                    ))}
                  </ul>
                  <a
                    href={whatsappUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="mt-6 inline-flex items-center gap-2 font-extrabold text-clay"
                  >
                    Tanya Paket Ini <ChevronRight size={17} className="transition group-hover:translate-x-1" />
                  </a>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section id="about-us" className="px-5 py-16 sm:px-8 lg:px-10">
        <div className="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-[0.95fr_1.05fr]">
          <div className="grid grid-cols-2 gap-4">
            <img src="/images/gallery-rice-terrace.jpg" alt="Traveler Tinggal Jalan" className="h-72 w-full rounded-[26px] object-cover shadow-soft" />
            <img src="/images/gallery-indonesia-green.jpg" alt="Galeri perjalanan" className="mt-10 h-72 w-full rounded-[26px] object-cover shadow-soft" />
          </div>
          <div>
            <p className="mb-3 text-sm font-bold uppercase tracking-[0.24em] text-clay">About Us</p>
            <h2 className="font-display text-4xl font-extrabold leading-tight sm:text-5xl">
              Teman perjalanan untuk itinerary yang lebih tertata.
            </h2>
            <p className="mt-6 text-lg leading-8 text-forest/72">
              Tinggal Jalan membantu traveler memilih destinasi, menyusun perjalanan, dan menikmati momen tanpa
              ribet. Setiap trip disiapkan agar terasa personal, hangat, dan tetap praktis dari awal sampai pulang.
            </p>
            <div className="mt-8 grid gap-4 sm:grid-cols-3">
              {['Private Trip', 'Open Trip', 'Custom Tour'].map((item) => (
                <div key={item} className={`rounded-2xl bg-white p-5 text-center shadow-soft hover:text-clay ${cardLift}`}>
                  <p className="font-extrabold">{item}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section id="get-inspiration" className="bg-forest px-5 py-16 text-white sm:px-8 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <SectionHeader eyebrow="Get Inspiration" title="Aktivitas yang bikin perjalanan hidup" light>
            Dari sunrise sampai kuliner lokal, setiap paket bisa dibuat sesuai gaya jalanmu.
          </SectionHeader>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {activities.map(({ title, icon: Icon, text }) => (
              <article key={title} className={`rounded-[24px] border border-white/10 bg-white/8 p-6 backdrop-blur hover:border-sunset/45 hover:bg-white/12 ${cardLift}`}>
                <div className="mb-8 grid h-12 w-12 place-items-center rounded-2xl bg-sunset text-forest">
                  <Icon size={24} />
                </div>
                <h3 className="text-xl font-extrabold">{title}</h3>
                <p className="mt-3 leading-7 text-white/70">{text}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section id="reviews" className="bg-white px-5 py-16 sm:px-8 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <SectionHeader eyebrow="Customer Stories" title="Review singkat dari traveler">
            Cerita singkat dari traveler yang sudah menikmati perjalanan bersama Tinggal Jalan.
          </SectionHeader>
          <div className="grid gap-6 lg:grid-cols-3">
            {testimonials.map((item) => (
              <article key={item.name} className={`rounded-[26px] border border-forest/10 bg-[#fffaf3] p-6 shadow-soft hover:border-clay/25 ${cardLift}`}>
                <div className="mb-5 flex text-sunset">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <Star key={star} size={18} fill="currentColor" />
                  ))}
                </div>
                <p className="min-h-28 text-lg leading-8 text-forest/76">"{item.text}"</p>
                <p className="mt-6 font-extrabold text-forest">
                  {item.name}, {item.country}
                </p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section id="activities" className="px-5 py-16 sm:px-8 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <SectionHeader eyebrow="Our Gallery" title="Momen perjalanan dari berbagai destinasi">
            Cuplikan suasana dari destinasi, aktivitas, dan rute perjalanan favorit.
          </SectionHeader>
          <div className="grid auto-rows-[220px] gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {galleryImages.map((item, index) => (
              <figure
                key={item.image}
                className={`group relative overflow-hidden rounded-[24px] shadow-soft ${cardLift} ${index === 0 || index === 4 ? 'lg:col-span-2' : ''} ${index === 1 ? 'lg:row-span-2' : ''}`}
              >
                <img
                  src={item.image}
                  alt={item.title}
                  className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-forest/75 via-transparent to-transparent transition group-hover:from-forest/90" />
                <figcaption className="absolute bottom-4 left-4 right-4 text-white transition duration-300 group-hover:-translate-y-1">
                  <p className="text-xs font-bold uppercase tracking-[0.18em] text-white/72">{item.location}</p>
                  <p className="mt-1 text-xl font-extrabold">{item.title}</p>
                </figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>

      <section id="booking" className="bg-white px-5 py-16 sm:px-8 lg:px-10">
        <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr]">
          <div>
            <p className="mb-3 text-sm font-bold uppercase tracking-[0.24em] text-clay">Booking</p>
            <h2 className="font-display text-4xl font-extrabold leading-tight sm:text-5xl">
              Konsultasi Trip via WhatsApp.
            </h2>
            <p className="mt-6 text-lg leading-8 text-forest/72">
              Isi kebutuhan perjalanan secara singkat. Tim Tinggal Jalan akan bantu rekomendasikan paket dan jadwal
              yang paling sesuai.
            </p>
            <a
              href={whatsappUrl}
              target="_blank"
              rel="noreferrer"
              className={`mt-8 inline-flex max-w-full items-center gap-4 rounded-2xl border border-forest/10 bg-sand p-4 shadow-soft hover:border-[#25D366]/40 ${buttonLift}`}
            >
              <div className="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-[#25D366] text-white">
                <MessageCircle size={28} />
              </div>
              <div>
                <p className="text-sm font-semibold text-forest/58">Butuh bantuan?</p>
                <p className="text-lg font-extrabold">Chat with us on WhatsApp</p>
              </div>
            </a>
          </div>
          <form className="rounded-[30px] bg-[#fffaf3] p-6 shadow-soft">
            <div className="grid gap-4 sm:grid-cols-2">
              {[
                ['Nama lengkap', 'Masukkan nama'],
                ['WhatsApp', '0812 0000 0000'],
              ].map(([label, placeholder]) => (
                <label key={label} className="block">
                  <span className="mb-2 block text-sm font-bold">{label}</span>
                  <input className={`h-13 w-full rounded-2xl border border-forest/10 bg-white px-4 py-4 outline-none ${fieldFocus}`} placeholder={placeholder} />
                </label>
              ))}
              {[
                ['date', 'Pilih Tanggal', ['25 Juni 2026', 'Akhir pekan ini', 'Bulan depan']],
                ['tripType', 'Type Trip', tripTypeOptions],
                ['destination', 'Destination', destinationOptions],
                ['pax', 'Pax', paxOptions],
              ].map(([key, label, options]) => (
                <SelectBox
                  key={key}
                  id={`booking-${key}`}
                  label={label}
                  value={bookingValues[key]}
                  options={options}
                  openSelect={openSelect}
                  setOpenSelect={setOpenSelect}
                  onChange={(option) => setBookingValues((current) => ({ ...current, [key]: option }))}
                  variant="form"
                />
              ))}
            </div>
            <button type="button" className={`mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-forest px-6 py-4 font-extrabold text-white hover:bg-ocean ${buttonLift}`}>
              <Send size={18} /> Kirim Permintaan Booking
            </button>
          </form>
        </div>
      </section>

      <section className="bg-forest px-5 pt-16 text-white sm:px-8 lg:px-10">
        <div className="mx-auto max-w-7xl rounded-[32px] border border-white/10 bg-white/8 p-6 shadow-2xl shadow-forest/20 backdrop-blur sm:p-8 lg:flex lg:items-center lg:justify-between lg:gap-10">
          <div className="max-w-2xl">
            <p className="mb-3 text-sm font-bold uppercase tracking-[0.24em] text-sunset">Ready to go</p>
            <h2 className="font-display text-3xl font-extrabold leading-tight sm:text-4xl">
              Siap rancang perjalanan berikutnya?
            </h2>
            <p className="mt-4 text-base leading-7 text-white/72">
              Ceritakan destinasi, tanggal, dan jumlah peserta. Tim Tinggal Jalan akan bantu pilihkan paket yang paling pas.
            </p>
          </div>
          <div className="mt-7 flex flex-col gap-3 sm:flex-row lg:mt-0">
            <a
              href={whatsappUrl}
              target="_blank"
              rel="noreferrer"
              className={`inline-flex items-center justify-center gap-2 rounded-full bg-sunset px-6 py-4 font-extrabold text-forest hover:bg-[#f0aa56] ${buttonLift}`}
            >
              <MessageCircle size={18} /> Konsultasi via WhatsApp
            </a>
            <a
              href="#packages"
              className={`group inline-flex items-center justify-center gap-2 rounded-full border border-white/20 bg-white/10 px-6 py-4 font-bold text-white hover:bg-white/16 ${buttonLift}`}
            >
              Lihat Paket <ChevronRight size={18} className="transition group-hover:translate-x-1" />
            </a>
          </div>
        </div>
      </section>

      <footer className="bg-forest px-5 pb-28 pt-12 text-white sm:px-8 lg:px-10">
        <div className="mx-auto grid max-w-7xl gap-10 border-b border-white/10 pb-10 md:grid-cols-2 xl:grid-cols-[1.25fr_0.75fr_0.8fr_1.2fr]">
          <div>
            <a href="#home" className="inline-flex items-center gap-3">
              <span className="grid h-12 w-12 place-items-center rounded-full bg-sunset text-lg font-black text-forest">
                TJ
              </span>
              <span>
                <span className="block text-2xl font-extrabold">Tinggal Jalan</span>
                <span className="mt-1 block text-sm font-semibold text-white/56">PT. TINGGAL JALAN AJA</span>
              </span>
            </a>
            <p className="mt-5 max-w-sm leading-7 text-white/68">
              Travel agency & consultant untuk private trip, custom tour, dan perjalanan grup yang lebih tertata.
            </p>
            <div className="mt-6 flex flex-wrap gap-2">
              {['Google 5.0', 'NPS 9.9/10', 'Local Guide'].map((item) => (
                <span key={item} className="rounded-full border border-white/12 bg-white/8 px-3 py-2 text-xs font-extrabold text-white/78">
                  {item}
                </span>
              ))}
            </div>
          </div>

          <div>
            <h3 className="font-extrabold">Explore</h3>
            <div className="mt-5 grid gap-3">
              {footerLinks.map((item) => (
                <a key={item.label} href={item.href} className="group inline-flex items-center gap-2 font-semibold text-white/68 transition hover:text-sunset">
                  <span className="h-px w-0 bg-sunset transition-all group-hover:w-4" />
                  {item.label}
                </a>
              ))}
            </div>
          </div>

          <div>
            <h3 className="font-extrabold">Destinations</h3>
            <div className="mt-5 grid gap-3">
              {footerDestinations.map((item) => (
                <a key={item} href="#destination" className="group inline-flex items-center gap-2 font-semibold text-white/68 transition hover:text-sunset">
                  <MapPin size={15} className="text-sunset/70 transition group-hover:text-sunset" />
                  {item}
                </a>
              ))}
            </div>
          </div>

          <div>
            <h3 className="font-extrabold">Contact</h3>
            <div className="mt-5 grid gap-4">
              {contactItems.map(({ icon: Icon, label, value }) => (
                <div key={label} className="group flex gap-3 rounded-2xl border border-white/8 bg-white/5 p-3 transition hover:border-sunset/30 hover:bg-white/8">
                  <div className="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white/8 text-sunset">
                    <Icon size={17} />
                  </div>
                  <div>
                    <p className="text-xs font-bold uppercase tracking-[0.14em] text-white/42">{label}</p>
                    <p className="mt-1 text-sm font-semibold leading-6 text-white/76">{value}</p>
                  </div>
                </div>
              ))}
            </div>
            <a
              href={whatsappUrl}
              target="_blank"
              rel="noreferrer"
              className={`mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] px-5 py-4 font-extrabold text-white hover:bg-[#1ebe5d] ${buttonLift}`}
            >
              <MessageCircle size={18} /> Chat WhatsApp
            </a>
          </div>
        </div>

        <div className="mx-auto flex max-w-7xl flex-col gap-4 pt-7 text-sm font-semibold text-white/52 sm:flex-row sm:items-center sm:justify-between">
          <p>© 2026 Tinggal Jalan. All rights reserved.</p>
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-5">
            <p>Travel Agency & Consultant • Malang, Indonesia</p>
            <a href="#home" className="group inline-flex items-center gap-2 text-white/70 transition hover:text-sunset">
              Kembali ke atas <ChevronRight size={15} className="transition group-hover:-translate-y-0.5 group-hover:rotate-[-90deg]" />
            </a>
          </div>
        </div>
      </footer>

      <a
        href={whatsappUrl}
        target="_blank"
        rel="noreferrer"
        className={`fixed bottom-5 right-5 z-40 inline-flex items-center gap-3 rounded-full bg-[#25D366] px-5 py-4 font-extrabold text-white shadow-2xl shadow-forest/25 hover:bg-[#1ebe5d] ${buttonLift}`}
        aria-label="Booking via WhatsApp"
      >
        <MessageCircle size={22} />
        <span className="hidden sm:inline">WhatsApp</span>
      </a>
    </main>
  );
}

export default App;
