import { getRouteById } from './routes';
import { getLocalized } from '../utils/localization';

export const newsCategories = [
  { value: 'all', label: { id: 'Semua', us: 'All' } },
  { value: 'panduan', label: { id: 'Panduan', us: 'Guide' } },
  { value: 'tips', label: { id: 'Tips', us: 'Tips' } },
  { value: 'itinerary', label: { id: 'Itinerary', us: 'Itinerary' } },
  { value: 'kabar', label: { id: 'Kabar', us: 'News' } },
];

export const newsArticles = [
  {
    slug: 'paket-wisata-bromo-dari-malang',
    title: {
      id: 'Paket Wisata Bromo dari Malang: Rute, Harga, dan Tips Booking',
      us: 'Bromo Tour Package from Malang: Route, Price, and Booking Tips',
    },
    excerpt: {
      id: 'Panduan singkat untuk memilih paket Bromo dari Malang, mulai dari jam pickup, rute jeep, sampai hal yang perlu dikonfirmasi sebelum bayar.',
      us: 'A practical guide to choosing a Bromo package from Malang, from pickup timing and jeep routes to what to confirm before payment.',
    },
    category: 'panduan',
    destinationId: 'bromo',
    publishedDate: '2026-06-14',
    updatedDate: '2026-06-14',
    readingTime: { id: '5 menit baca', us: '5 min read' },
    coverImage: '/images/routes/bromo-jeep.jpg',
    coverAlt: { id: 'Jeep di area Bromo saat perjalanan sunrise', us: 'Jeep in the Bromo area during a sunrise trip' },
    tags: ['Bromo', 'Malang', 'Sunrise', 'Private Trip'],
    relatedRouteIds: ['bromo-sunrise', 'bromo-family-jeep', 'bromo-madakaripura'],
    seo: {
      title: {
        id: 'Paket Wisata Bromo dari Malang | Harga & Itinerary | Tinggal Jalan',
        us: 'Bromo Tour Package from Malang | Price & Itinerary | Tinggal Jalan',
      },
      description: {
        id: 'Cari paket wisata Bromo dari Malang? Pelajari rute, estimasi harga, jam pickup, fasilitas, dan tips booking private trip Bromo.',
        us: 'Looking for a Bromo tour package from Malang? Learn routes, price estimates, pickup timing, inclusions, and private trip booking tips.',
      },
    },
    sections: [
      {
        heading: { id: 'Kenapa mulai dari Malang?', us: 'Why start from Malang?' },
        body: {
          id: 'Malang sering jadi titik mulai yang nyaman karena jaraknya lebih dekat ke area Bromo dibanding banyak kota besar lain. Untuk sunrise, pickup biasanya dilakukan tengah malam agar masih ada waktu transfer ke jeep dan viewpoint.',
          us: 'Malang is often a comfortable starting point because it is closer to the Bromo area than many larger cities. For sunrise trips, pickup usually happens around midnight so there is enough time for the jeep transfer and viewpoint stop.',
        },
      },
      {
        heading: { id: 'Apa yang perlu dicek sebelum booking?', us: 'What should you check before booking?' },
        body: {
          id: 'Pastikan titik pickup, tipe jeep, estimasi tiket masuk, durasi stop, dan apakah harga sudah termasuk dokumentasi dasar. Untuk grup keluarga, tanyakan juga opsi rute yang minim jalan kaki.',
          us: 'Confirm the pickup point, jeep type, entrance ticket estimate, stop duration, and whether basic documentation is included. For family groups, also ask for a route with less walking.',
        },
      },
      {
        heading: { id: 'Kapan rute ini cocok?', us: 'When is this route a good fit?' },
        body: {
          id: 'Rute Bromo dari Malang cocok untuk first timer, traveler yang ingin sunrise klasik, dan grup yang butuh jadwal ringkas. Jika ingin perjalanan lebih santai, pilih paket private agar stop bisa disesuaikan.',
          us: 'A Bromo route from Malang works well for first timers, travelers chasing the classic sunrise, and groups needing a compact schedule. Choose a private package if you want the stops to stay flexible.',
        },
      },
    ],
  },
  {
    slug: 'tips-ke-tumpak-sewu-untuk-pemula',
    title: {
      id: 'Tips ke Tumpak Sewu untuk Pemula: Trek, Waktu Terbaik, dan Persiapan',
      us: 'Tumpak Sewu Tips for First Timers: Trek, Best Time, and Preparation',
    },
    excerpt: {
      id: 'Tumpak Sewu indah, tapi aksesnya perlu persiapan. Ini hal yang perlu diketahui sebelum memilih rute panorama atau lower trek.',
      us: 'Tumpak Sewu is beautiful, but access needs preparation. Here is what to know before choosing the panorama route or lower trek.',
    },
    category: 'tips',
    destinationId: 'tumpak-sewu',
    publishedDate: '2026-06-13',
    updatedDate: '2026-06-13',
    readingTime: { id: '4 menit baca', us: '4 min read' },
    coverImage: '/images/routes/tumpak-sewu.jpg',
    coverAlt: { id: 'Panorama Air Terjun Tumpak Sewu', us: 'Tumpak Sewu waterfall panorama' },
    tags: ['Tumpak Sewu', 'Waterfall', 'Trekking', 'East Java'],
    relatedRouteIds: ['tumpak-sewu-daytrip', 'tumpak-sewu-lower-trek', 'tumpak-kapas-biru'],
    seo: {
      title: {
        id: 'Tips ke Tumpak Sewu untuk Pemula | Tinggal Jalan',
        us: 'Tumpak Sewu Tips for First Timers | Tinggal Jalan',
      },
      description: {
        id: 'Panduan Tumpak Sewu untuk pemula: pilihan rute, persiapan trekking, waktu terbaik, dan tips memilih private trip air terjun.',
        us: 'A first-timer guide to Tumpak Sewu: route options, trekking preparation, best timing, and tips for choosing a waterfall private trip.',
      },
    },
    sections: [
      {
        heading: { id: 'Pilih rute sesuai kondisi grup', us: 'Choose the route around your group' },
        body: {
          id: 'Tidak semua traveler perlu turun sampai area bawah air terjun. Panorama point lebih ringan, sementara lower trek memberi pengalaman lebih dekat tetapi membutuhkan tenaga, sepatu nyaman, dan kesiapan melewati jalur basah.',
          us: 'Not every traveler needs to descend to the lower waterfall area. The panorama point is easier, while the lower trek feels more immersive but requires energy, proper shoes, and comfort with wet paths.',
        },
      },
      {
        heading: { id: 'Bawa perlengkapan yang tepat', us: 'Bring the right gear' },
        body: {
          id: 'Gunakan sandal gunung atau sepatu yang grip-nya kuat, bawa dry bag kecil, dan siapkan pakaian ganti. Hindari membawa barang terlalu banyak karena beberapa jalur cukup sempit.',
          us: 'Use trekking sandals or shoes with strong grip, bring a small dry bag, and prepare spare clothes. Avoid carrying too much because parts of the trail can be narrow.',
        },
      },
      {
        heading: { id: 'Kenapa private trip membantu?', us: 'Why private trips help' },
        body: {
          id: 'Private trip membuat jam berangkat, ritme trekking, dan kombinasi stop lebih mudah disesuaikan. Ini penting bila grup punya anak, senior, atau traveler yang belum terbiasa trekking.',
          us: 'A private trip makes departure timing, trekking pace, and stop combinations easier to adjust. This matters for groups with children, seniors, or travelers who are not used to trekking.',
        },
      },
    ],
  },
  {
    slug: 'itinerary-jogja-3-hari-2-malam',
    title: {
      id: 'Itinerary Jogja 3 Hari 2 Malam: Heritage, Kuliner, dan Merapi',
      us: 'Jogja 3D2N Itinerary: Heritage, Food, and Merapi',
    },
    excerpt: {
      id: 'Contoh alur perjalanan Jogja yang seimbang untuk keluarga atau grup kecil: candi, kota, kuliner, dan outdoor ringan.',
      us: 'A balanced Jogja travel flow for families or small groups: temples, city stops, local food, and light outdoor moments.',
    },
    category: 'itinerary',
    destinationId: 'jogja',
    publishedDate: '2026-06-12',
    updatedDate: '2026-06-12',
    readingTime: { id: '6 menit baca', us: '6 min read' },
    coverImage: '/images/routes/jogja.jpg',
    coverAlt: { id: 'Area heritage Jogja saat perjalanan budaya', us: 'Jogja heritage area during a cultural trip' },
    tags: ['Jogja', 'Itinerary', 'Heritage', 'Family Trip'],
    relatedRouteIds: ['jogja-heritage', 'jogja-prambanan-city', 'jogja-merapi-jeep'],
    seo: {
      title: {
        id: 'Itinerary Jogja 3 Hari 2 Malam | Heritage & Kuliner',
        us: 'Jogja 3D2N Itinerary | Heritage & Culinary Travel',
      },
      description: {
        id: 'Contoh itinerary Jogja 3 hari 2 malam untuk heritage, kuliner, Prambanan, Merapi, dan trip keluarga dengan tempo fleksibel.',
        us: 'A sample Jogja 3D2N itinerary for heritage, food, Prambanan, Merapi, and family travel with a flexible pace.',
      },
    },
    sections: [
      {
        heading: { id: 'Hari pertama: adaptasi dan kuliner kota', us: 'Day one: settle in and taste the city' },
        body: {
          id: 'Mulai dengan pickup, check-in, lalu rute kota yang ringan. Sore dan malam bisa dipakai untuk kuliner lokal agar traveler tidak langsung lelah setelah perjalanan masuk Jogja.',
          us: 'Start with pickup, check-in, then a light city route. Late afternoon and evening can focus on local food so travelers do not get tired immediately after arriving in Jogja.',
        },
      },
      {
        heading: { id: 'Hari kedua: heritage dan Prambanan', us: 'Day two: heritage and Prambanan' },
        body: {
          id: 'Hari kedua cocok untuk candi, cerita budaya, dan spot foto. Jika membawa anak atau senior, atur durasi stop lebih pendek dan sisakan waktu istirahat di tengah hari.',
          us: 'Day two works well for temples, cultural context, and photo stops. If traveling with children or seniors, keep stops shorter and leave rest time in the middle of the day.',
        },
      },
      {
        heading: { id: 'Hari ketiga: Merapi atau belanja ringan', us: 'Day three: Merapi or easy shopping' },
        body: {
          id: 'Sebelum pulang, pilih Merapi Jeep untuk outdoor ringan atau rute belanja oleh-oleh. Pilihan terbaik tergantung jam kereta, penerbangan, dan energi grup.',
          us: 'Before departure, choose a Merapi Jeep route for light outdoor activity or an easy souvenir stop. The best option depends on train time, flight time, and group energy.',
        },
      },
    ],
  },
  {
    slug: 'liburan-keluarga-medan-danau-toba',
    title: {
      id: 'Liburan Keluarga Medan dan Danau Toba: Cara Membuat Rute Lebih Nyaman',
      us: 'Medan and Lake Toba Family Trip: How to Make the Route More Comfortable',
    },
    excerpt: {
      id: 'Lake Toba punya jarak tempuh panjang. Untuk keluarga, itinerary perlu dibuat realistis agar perjalanan tetap menyenangkan.',
      us: 'Lake Toba involves longer road time. For families, the itinerary should stay realistic so the trip remains enjoyable.',
    },
    category: 'panduan',
    destinationId: 'medan',
    publishedDate: '2026-06-11',
    updatedDate: '2026-06-11',
    readingTime: { id: '5 menit baca', us: '5 min read' },
    coverImage: '/images/routes/medan.jpg',
    coverAlt: { id: 'Pemandangan rute Medan dan Danau Toba', us: 'Scenery on the Medan and Lake Toba route' },
    tags: ['Medan', 'Lake Toba', 'Family Trip', 'Sumatra'],
    relatedRouteIds: ['medan-lake-toba', 'medan-toba-family', 'medan-berastagi'],
    seo: {
      title: {
        id: 'Liburan Keluarga Medan dan Danau Toba | Tinggal Jalan',
        us: 'Medan and Lake Toba Family Trip | Tinggal Jalan',
      },
      description: {
        id: 'Tips membuat rute Medan dan Danau Toba lebih nyaman untuk keluarga: durasi perjalanan, stop istirahat, dan pilihan private trip.',
        us: 'Tips for a more comfortable Medan and Lake Toba family route: road time, rest stops, and private trip options.',
      },
    },
    sections: [
      {
        heading: { id: 'Jangan terlalu padat di hari pertama', us: 'Do not overload the first day' },
        body: {
          id: 'Setelah tiba di Medan, hindari langsung mengisi terlalu banyak stop. Rute menuju Toba butuh waktu, jadi lebih baik memilih beberapa stop yang benar-benar penting.',
          us: 'After arriving in Medan, avoid packing too many stops immediately. The route toward Toba takes time, so it is better to choose a few stops that truly matter.',
        },
      },
      {
        heading: { id: 'Atur stop makan dan toilet dengan jelas', us: 'Plan food and restroom stops clearly' },
        body: {
          id: 'Untuk keluarga, kenyamanan sering ditentukan oleh stop kecil: makan, toilet, camilan, dan waktu stretching. Komunikasikan kebutuhan ini sejak awal dengan tim trip.',
          us: 'For families, comfort often depends on small stops: meals, restrooms, snacks, and stretching time. Communicate these needs with the trip team from the start.',
        },
      },
      {
        heading: { id: 'Gunakan kendaraan yang cukup lega', us: 'Use a vehicle with enough room' },
        body: {
          id: 'Rute Sumatra Utara akan terasa lebih nyaman jika bagasi, stroller, dan ruang kaki sudah dipikirkan. Untuk grup keluarga, upgrade unit sering lebih penting daripada menambah banyak destinasi.',
          us: 'North Sumatra routes feel more comfortable when luggage, strollers, and legroom are considered. For family groups, a vehicle upgrade is often more valuable than adding extra destinations.',
        },
      },
    ],
  },
  {
    slug: 'cara-memilih-private-trip-indonesia',
    title: {
      id: 'Cara Memilih Private Trip Indonesia yang Aman dan Jelas',
      us: 'How to Choose a Safe and Clear Indonesia Private Trip',
    },
    excerpt: {
      id: 'Private trip bukan cuma soal mobil sendiri. Yang penting adalah alur komunikasi, itinerary, pickup, dan konfirmasi harga yang transparan.',
      us: 'A private trip is not only about having your own car. Clear communication, itinerary, pickup, and transparent price confirmation matter more.',
    },
    category: 'tips',
    destinationId: 'indonesia',
    publishedDate: '2026-06-10',
    updatedDate: '2026-06-10',
    readingTime: { id: '4 menit baca', us: '4 min read' },
    coverImage: '/images/gallery-indonesia-green.jpg',
    coverAlt: { id: 'Pemandangan hijau perjalanan Indonesia', us: 'Green Indonesia travel scenery' },
    tags: ['Private Trip', 'Travel Tips', 'Indonesia', 'Booking'],
    relatedRouteIds: ['bromo-sunrise', 'jogja-heritage', 'medan-lake-toba'],
    seo: {
      title: {
        id: 'Cara Memilih Private Trip Indonesia | Tips Aman Booking',
        us: 'How to Choose an Indonesia Private Trip | Safe Booking Tips',
      },
      description: {
        id: 'Tips memilih private trip Indonesia: cek itinerary, pickup, harga, fasilitas, komunikasi WhatsApp, dan konfirmasi sebelum pembayaran.',
        us: 'Tips for choosing an Indonesia private trip: check itinerary, pickup, price, inclusions, WhatsApp communication, and confirmation before payment.',
      },
    },
    sections: [
      {
        heading: { id: 'Cari itinerary yang spesifik', us: 'Look for a specific itinerary' },
        body: {
          id: 'Itinerary yang baik menyebutkan alur stop, estimasi waktu, fasilitas, dan catatan penting. Hindari paket yang terlalu umum tanpa detail pickup dan durasi.',
          us: 'A good itinerary explains the stop sequence, timing estimate, inclusions, and important notes. Avoid packages that are too generic without pickup and duration details.',
        },
      },
      {
        heading: { id: 'Minta konfirmasi sebelum bayar', us: 'Ask for confirmation before payment' },
        body: {
          id: 'Tanggal, driver, kendaraan, jeep, dan tiket lokal sebaiknya dikonfirmasi dulu. Ini membuat pembayaran lebih aman dan mengurangi perubahan mendadak.',
          us: 'Dates, driver, vehicle, jeep, and local tickets should be confirmed first. This makes payment safer and reduces last-minute changes.',
        },
      },
      {
        heading: { id: 'Pastikan kanal komunikasi jelas', us: 'Make sure communication is clear' },
        body: {
          id: 'WhatsApp yang responsif sangat membantu, terutama untuk pickup dini hari, perubahan cuaca, atau kebutuhan khusus saat perjalanan.',
          us: 'A responsive WhatsApp channel helps a lot, especially for early pickup, weather changes, or special needs during the trip.',
        },
      },
    ],
  },
  {
    slug: 'kabar-rute-baru-tinggal-jalan',
    title: {
      id: 'Kabar Tinggal Jalan: Rute Pilihan Kini Lebih Mudah Dibandingkan',
      us: 'Tinggal Jalan Update: Featured Routes Are Now Easier to Compare',
    },
    excerpt: {
      id: 'Update pengalaman website Tinggal Jalan membantu traveler membandingkan rute Bromo, Jogja, Tumpak Sewu, dan Medan dengan lebih jelas.',
      us: 'The updated Tinggal Jalan website experience helps travelers compare Bromo, Jogja, Tumpak Sewu, and Medan routes more clearly.',
    },
    category: 'kabar',
    destinationId: 'indonesia',
    publishedDate: '2026-06-09',
    updatedDate: '2026-06-09',
    readingTime: { id: '3 menit baca', us: '3 min read' },
    coverImage: '/images/hero-bromo.jpg',
    coverAlt: { id: 'Traveler melihat pemandangan Bromo', us: 'Traveler looking at Bromo scenery' },
    tags: ['News', 'Tinggal Jalan', 'Routes', 'Website Update'],
    relatedRouteIds: ['bromo-tumpak', 'jogja-bromo-overland', 'tumpak-bromo-2d1n'],
    seo: {
      title: {
        id: 'Kabar Tinggal Jalan | Rute Wisata Lebih Mudah Dibandingkan',
        us: 'Tinggal Jalan News | Easier Tour Route Comparison',
      },
      description: {
        id: 'Kabar terbaru Tinggal Jalan: pengalaman membandingkan rute wisata Indonesia kini dibuat lebih jelas untuk traveler.',
        us: 'Latest Tinggal Jalan update: comparing Indonesia tour routes is now clearer for travelers.',
      },
    },
    sections: [
      {
        heading: { id: 'Fokus pada rute yang mudah dipahami', us: 'Focused on routes that are easy to understand' },
        body: {
          id: 'Setiap rute dibuat dengan ringkasan, highlight, pickup, dan catatan penting agar traveler bisa membandingkan sebelum menghubungi tim.',
          us: 'Each route includes a summary, highlights, pickup notes, and important details so travelers can compare before contacting the team.',
        },
      },
      {
        heading: { id: 'Booking tetap lewat WhatsApp', us: 'Booking still happens through WhatsApp' },
        body: {
          id: 'Website membantu traveler memahami pilihan. Setelah itu, tim Tinggal Jalan tetap mengonfirmasi jadwal, kendaraan, dan kebutuhan khusus lewat WhatsApp.',
          us: 'The website helps travelers understand their options. After that, the Tinggal Jalan team still confirms schedule, vehicle, and special needs through WhatsApp.',
        },
      },
      {
        heading: { id: 'Artikel akan terus ditambah', us: 'More articles will be added over time' },
        body: {
          id: 'Bagian Berita & Panduan disiapkan untuk tips perjalanan, update rute, dan panduan destinasi agar pengunjung bisa merencanakan trip dengan lebih percaya diri.',
          us: 'The News & Guides section is prepared for travel tips, route updates, and destination guides so visitors can plan with more confidence.',
        },
      },
    ],
  },
];

export function getNewsArticleBySlug(slug) {
  return newsArticles.find((article) => article.slug === slug);
}

export function getLatestNewsArticles(limit = 3) {
  return [...newsArticles]
    .sort((first, second) => new Date(second.publishedDate) - new Date(first.publishedDate))
    .slice(0, limit);
}

export function getFeaturedNewsArticles() {
  const [featured, ...latest] = getLatestNewsArticles(3);

  return {
    featured,
    latest,
  };
}

export function getPrimaryRelatedRoute(article) {
  return getRouteById(article.relatedRouteIds?.[0]);
}

export function getNewsByFilters({ search = '', category = 'all', destination = 'all', language = 'id' } = {}) {
  return newsArticles.filter((article) => {
    const categoryMatches = category === 'all' || article.category === category;
    const destinationMatches = destination === 'all' || article.destinationId === destination;

    if (!categoryMatches || !destinationMatches) {
      return false;
    }

    if (!search) {
      return true;
    }

    const primaryRoute = getPrimaryRelatedRoute(article);
    const haystack = [
      getLocalized(article.title, language),
      getLocalized(article.excerpt, language),
      getLocalized(primaryRoute?.title, language),
      article.destinationId,
      ...article.tags,
    ]
      .join(' ')
      .toLowerCase();

    return haystack.includes(search.toLowerCase());
  });
}

export function getRelatedNewsArticles({ routeId, destinationId, excludeSlug, limit = 3 } = {}) {
  return newsArticles
    .filter((article) => article.slug !== excludeSlug)
    .filter((article) => {
      if (routeId && article.relatedRouteIds?.includes(routeId)) {
        return true;
      }

      return destinationId && article.destinationId === destinationId;
    })
    .slice(0, limit);
}
