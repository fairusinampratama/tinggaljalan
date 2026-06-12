import { destinations, getDestinationById } from './destinations';

const PRICE_NOTE = 'Estimated price; final operational price is confirmed after schedule and availability checks.';

const defaultPackagePolicies = {
  cancellation: {
    id: 'Pembatalan gratis sampai 24 jam sebelum jadwal, setelah tim mengonfirmasi ketersediaan final.',
    cn: '团队确认最终可用性后，出发前24小时以前可免费取消。',
    us: 'Free cancellation up to 24 hours before departure after the team confirms final availability.',
  },
  confirmation: {
    id: 'Tim akan konfirmasi driver, jeep, pickup point, dan estimasi harga final lewat WhatsApp sebelum pembayaran.',
    cn: '付款前，团队会通过 WhatsApp 确认司机、吉普车、接送点和最终预估价格。',
    us: 'The team confirms driver, jeep, pickup point, and final estimated price via WhatsApp before payment.',
  },
};

const defaultGoodToKnow = [
  {
    id: 'Harga di halaman ini masih estimasi sampai jadwal dan kebutuhan grup dikonfirmasi.',
    cn: '页面价格为预估，实际价格需确认日期和团队需求后确定。',
    us: 'Prices on this page are estimates until schedule and group needs are confirmed.',
  },
  {
    id: 'Pickup, drop-off, dan urutan stop bisa disesuaikan dengan kondisi lokal.',
    cn: '接送点、送达点和游览顺序可根据当地情况调整。',
    us: 'Pickup, drop-off, and stop order can be adjusted around local conditions.',
  },
  {
    id: 'Ketersediaan dapat berubah karena cuaca, regulasi lokal, atau periode ramai.',
    cn: '可用性可能因天气、当地规定或旺季而变化。',
    us: 'Availability can change because of weather, local regulation, or peak periods.',
  },
];

const routeAddOnsByDestination = {
  bromo: [
    {
      id: 'hotel-bromo-area',
      title: { id: 'Hotel area Bromo', cn: 'Bromo 区域酒店', us: 'Bromo area hotel' },
      description: { id: 'Bantuan request hotel dekat area Bromo.', cn: '协助预订 Bromo 附近酒店。', us: 'Hotel request support near the Bromo area.' },
      priceIdr: 350000,
      priceUsd: 24,
      pricing: 'perBooking',
    },
    {
      id: 'private-car-upgrade',
      title: { id: 'Upgrade mobil private', cn: '私人车辆升级', us: 'Private car upgrade' },
      description: { id: 'Unit lebih nyaman untuk pickup dan drop-off.', cn: '更舒适的接送车辆。', us: 'A more comfortable unit for pickup and drop-off.' },
      priceIdr: 250000,
      priceUsd: 18,
      pricing: 'perBooking',
    },
    {
      id: 'local-guide',
      title: { id: 'Guide lokal tambahan', cn: '额外当地向导', us: 'Additional local guide' },
      description: { id: 'Pendamping lokal untuk koordinasi dan cerita area.', cn: '当地陪同协助协调和讲解。', us: 'Local support for coordination and area context.' },
      priceIdr: 150000,
      priceUsd: 12,
      pricing: 'perBooking',
    },
  ],
  jogja: [
    {
      id: 'jogja-hotel-help',
      title: { id: 'Bantuan hotel Jogja', cn: 'Jogja 酒店协助', us: 'Jogja hotel support' },
      description: { id: 'Rekomendasi area menginap sesuai rute.', cn: '根据路线推荐住宿区域。', us: 'Stay-area recommendations matched to the route.' },
      priceIdr: 200000,
      priceUsd: 15,
      pricing: 'perBooking',
    },
    {
      id: 'culinary-stop',
      title: { id: 'Stop kuliner tambahan', cn: '额外美食停留', us: 'Extra culinary stop' },
      description: { id: 'Tambah satu stop makanan lokal sesuai waktu.', cn: '根据时间增加一个当地美食点。', us: 'Add one local food stop when timing allows.' },
      priceIdr: 75000,
      priceUsd: 6,
      pricing: 'perPax',
    },
    {
      id: 'airport-transfer',
      title: { id: 'Airport transfer', cn: '机场接送', us: 'Airport transfer' },
      description: { id: 'Pickup atau drop-off bandara di luar rundown utama.', cn: '主行程外的机场接送。', us: 'Airport pickup or drop-off outside the main rundown.' },
      priceIdr: 175000,
      priceUsd: 13,
      pricing: 'perBooking',
    },
  ],
  'tumpak-sewu': [
    {
      id: 'trekking-guide',
      title: { id: 'Guide trekking lokal', cn: '当地徒步向导', us: 'Local trekking guide' },
      description: { id: 'Guide tambahan untuk akses jalur basah.', cn: '湿滑路线额外向导。', us: 'Extra guide support for wet trail access.' },
      priceIdr: 175000,
      priceUsd: 13,
      pricing: 'perBooking',
    },
    {
      id: 'waterfall-raincoat',
      title: { id: 'Ponco waterfall', cn: '瀑布雨衣', us: 'Waterfall poncho' },
      description: { id: 'Ponco sederhana untuk area waterfall.', cn: '瀑布区域简易雨衣。', us: 'Simple poncho for the waterfall area.' },
      priceIdr: 35000,
      priceUsd: 3,
      pricing: 'perPax',
    },
    {
      id: 'lumajang-hotel-help',
      title: { id: 'Bantuan hotel Lumajang', cn: 'Lumajang 酒店协助', us: 'Lumajang hotel support' },
      description: { id: 'Request hotel dekat titik waterfall.', cn: '协助预订瀑布附近酒店。', us: 'Hotel request support near the waterfall access.' },
      priceIdr: 300000,
      priceUsd: 22,
      pricing: 'perBooking',
    },
  ],
  medan: [
    {
      id: 'medan-hotel-help',
      title: { id: 'Bantuan hotel Medan/Toba', cn: 'Medan/Toba 酒店协助', us: 'Medan/Toba hotel support' },
      description: { id: 'Bantuan request hotel sesuai jalur.', cn: '根据路线协助酒店请求。', us: 'Hotel request support matched to the route.' },
      priceIdr: 350000,
      priceUsd: 24,
      pricing: 'perBooking',
    },
    {
      id: 'family-car-upgrade',
      title: { id: 'Upgrade mobil keluarga', cn: '家庭车辆升级', us: 'Family car upgrade' },
      description: { id: 'Unit lebih lega untuk rute Sumatra Utara.', cn: '北苏门答腊路线更宽敞车辆。', us: 'Roomier vehicle for North Sumatra routes.' },
      priceIdr: 300000,
      priceUsd: 22,
      pricing: 'perBooking',
    },
    {
      id: 'local-food-stop',
      title: { id: 'Stop kuliner lokal', cn: '当地美食停留', us: 'Local food stop' },
      description: { id: 'Tambah stop makanan lokal sesuai jam perjalanan.', cn: '根据行程时间增加当地美食点。', us: 'Add a local food stop when travel timing allows.' },
      priceIdr: 85000,
      priceUsd: 7,
      pricing: 'perPax',
    },
  ],
};

const destinationFallbackImage = {
  bromo: '/images/routes/bromo-jeep.jpg',
  jogja: '/images/routes/jogja.jpg',
  'tumpak-sewu': '/images/routes/tumpak-sewu.jpg',
  medan: '/images/routes/medan.jpg',
};

const routeImageById = {
  'bromo-sunrise': '/images/routes/unique/bromo-sunrise.jpg',
  'bromo-madakaripura': '/images/routes/unique/bromo-madakaripura.jpg',
  'jogja-heritage': '/images/routes/unique/jogja-heritage.jpg',
  'bromo-family-jeep': '/images/routes/unique/bromo-family-jeep.jpg',
  'bromo-camping': '/images/routes/unique/bromo-camping.jpg',
  'bromo-photography': '/images/routes/unique/bromo-photography.jpg',
  'tumpak-sewu-daytrip': '/images/routes/unique/tumpak-sewu-daytrip.jpg',
  'tumpak-sewu-lower-trek': '/images/routes/unique/tumpak-sewu-lower-trek.jpg',
  'tumpak-kapas-biru': '/images/routes/unique/tumpak-kapas-biru.png',
  'tumpak-bromo-2d1n': '/images/routes/unique/tumpak-bromo-2d1n.jpg',
  'jogja-prambanan-city': '/images/routes/unique/jogja-prambanan-city.jpg',
  'jogja-merapi-jeep': '/images/routes/unique/jogja-merapi-jeep.jpg',
  'jogja-family-slow': '/images/routes/unique/jogja-family-slow.jpg',
  'jogja-culinary-night': '/images/routes/unique/jogja-culinary-night.jpg',
  'medan-lake-toba': '/images/routes/unique/medan-lake-toba.jpg',
  'medan-culinary': '/images/routes/unique/medan-culinary.jpg',
  'medan-berastagi': '/images/routes/unique/medan-berastagi.jpg',
  'medan-toba-family': '/images/routes/unique/medan-toba-family.jpg',
  'bromo-tumpak': '/images/routes/unique/bromo-tumpak.jpg',
  'jogja-bromo-overland': '/images/routes/unique/jogja-bromo-overland.jpg',
};

const routeImageAltById = {
  'bromo-sunrise': 'Sunrise view around Mount Bromo for a private jeep package',
  'bromo-madakaripura': 'Madakaripura waterfall for a Bromo waterfall add-on package',
  'jogja-heritage': 'Prambanan temple complex for a Jogja heritage route',
  'bromo-family-jeep': 'Bromo jeep landscape for a family-friendly route',
  'bromo-camping': 'Bromo highland landscape for a camping and stargazing package',
  'bromo-photography': 'Bromo caldera panorama for a photography-focused route',
  'tumpak-sewu-daytrip': 'Tumpak Sewu waterfall for a private day trip',
  'tumpak-sewu-lower-trek': 'Tumpak Sewu waterfall trail visual for a lower trek package',
  'tumpak-kapas-biru': 'East Java waterfall visual for a Kapas Biru and Tumpak Sewu package',
  'tumpak-bromo-2d1n': 'Tumpak Sewu waterfall visual for a waterfall and Bromo combo route',
  'jogja-prambanan-city': 'Prambanan temple for a Jogja city and heritage route',
  'jogja-merapi-jeep': 'Yogyakarta heritage visual used for a Merapi jeep package placeholder',
  'jogja-family-slow': 'Yogyakarta city visual for a family slow trip package',
  'jogja-culinary-night': 'Yogyakarta city visual for a culinary night route',
  'medan-lake-toba': 'North Sumatra landscape visual for a Lake Toba package',
  'medan-culinary': 'Medan city visual for a culinary short escape',
  'medan-berastagi': 'North Sumatra visual for a Berastagi highland package',
  'medan-toba-family': 'North Sumatra landscape visual for a family Lake Toba route',
  'bromo-tumpak': 'Tumpak Sewu waterfall visual for a Bromo and waterfall combo',
  'jogja-bromo-overland': 'Bromo jeep visual for a Java overland package',
};

const imageCreditByDestination = {
  bromo: 'Unsplash source listed in public/images/credits.md; used as Bromo/jeep visual placeholder.',
  jogja: 'Unsplash source listed in public/images/credits.md; used as Jogja heritage visual placeholder.',
  'tumpak-sewu': 'Unsplash source listed in public/images/credits.md; used as Tumpak Sewu waterfall visual placeholder.',
  medan: 'Unsplash source listed in public/images/credits.md; used as Medan/North Sumatra visual placeholder.',
};

const sourceRefsByDestination = {
  bromo: ['https://mountbromo.org/', 'https://www.bromo.co.id/'],
  jogja: ['https://www.indonesia.travel/id/en/destination/java/yogyakarta/yogyakarta---prambanan/'],
  'tumpak-sewu': ['https://www.airterjuntumpaksewu.com/'],
  medan: ['https://www.roughguides.com/indonesia/sumatra/lake-toba/'],
};

const routeDiscoveryById = {
  'bromo-sunrise': {
    styles: ['recommended', 'sunrise', 'private'],
    badge: 'Best seller',
    bestFor: 'First-time Bromo travelers who want the classic sunrise and jeep route.',
  },
  'bromo-madakaripura': {
    styles: ['recommended', 'sunrise', 'waterfall', 'private'],
    badge: 'Waterfall add-on',
    bestFor: 'Travelers who want Bromo sunrise plus one stronger nature stop.',
  },
  'jogja-heritage': {
    styles: ['recommended', 'culture', 'family', 'private'],
    badge: 'Culture pick',
    bestFor: 'Groups who want temples, food, and a relaxed Jogja pace.',
  },
  'bromo-family-jeep': {
    styles: ['family', 'sunrise', 'private'],
    badge: 'Family friendly',
    bestFor: 'Families and mixed-age groups who prefer less walking.',
  },
  'bromo-camping': {
    styles: ['adventure', 'multi-day', 'sunrise'],
    badge: 'Outdoor',
    bestFor: 'Travelers who want a slower mountain night before sunrise.',
  },
  'bromo-photography': {
    styles: ['sunrise', 'private'],
    badge: 'Photo route',
    bestFor: 'Travelers who want more time at viewpoints and jeep photo stops.',
  },
  'tumpak-sewu-daytrip': {
    styles: ['recommended', 'waterfall', 'adventure', 'private'],
    badge: 'Waterfall trip',
    bestFor: 'Travelers who want the Tumpak Sewu panorama without staying overnight.',
  },
  'tumpak-sewu-lower-trek': {
    styles: ['adventure', 'waterfall'],
    badge: 'Trekking',
    bestFor: 'Active travelers comfortable with wet trails and steeper access.',
  },
  'tumpak-kapas-biru': {
    styles: ['adventure', 'waterfall', 'multi-day'],
    badge: 'Photo route',
    bestFor: 'Waterfall-focused travelers who want more than one Lumajang stop.',
  },
  'tumpak-bromo-2d1n': {
    styles: ['recommended', 'adventure', 'waterfall', 'multi-day', 'sunrise'],
    badge: 'Combo',
    bestFor: 'Travelers comparing waterfall and volcano scenery in two days.',
  },
  'jogja-prambanan-city': {
    styles: ['culture', 'family', 'private'],
    badge: 'Heritage',
    bestFor: 'Travelers with one day for Prambanan, city stops, and local food.',
  },
  'jogja-merapi-jeep': {
    styles: ['adventure', 'private'],
    badge: 'Jeep',
    bestFor: 'Jogja travelers who want an outdoor route without a hard trek.',
  },
  'jogja-family-slow': {
    styles: ['family', 'culture', 'multi-day', 'private'],
    badge: 'Family friendly',
    bestFor: 'Families who want culture and food with flexible rest time.',
  },
  'jogja-culinary-night': {
    styles: ['culture', 'private'],
    badge: 'Culinary',
    bestFor: 'Short-stay travelers looking for an easy evening route.',
  },
  'medan-lake-toba': {
    styles: ['recommended', 'family', 'multi-day', 'private'],
    badge: 'Lake Toba',
    bestFor: 'Travelers arriving in Medan who want an organized Lake Toba route.',
  },
  'medan-culinary': {
    styles: ['culture', 'family', 'private'],
    badge: 'Culinary',
    bestFor: 'Short-stay travelers who want Medan food and city stops.',
  },
  'medan-berastagi': {
    styles: ['family', 'multi-day', 'private'],
    badge: 'Highland',
    bestFor: 'Groups who want cooler highland scenery near Medan.',
  },
  'medan-toba-family': {
    styles: ['recommended', 'family', 'multi-day', 'private'],
    badge: 'Family route',
    bestFor: 'Families who want Lake Toba with manageable travel days.',
  },
  'bromo-tumpak': {
    styles: ['recommended', 'adventure', 'waterfall', 'multi-day', 'sunrise'],
    badge: 'Recommended',
    bestFor: 'Travelers who want Bromo sunrise and Tumpak Sewu in one East Java plan.',
  },
  'jogja-bromo-overland': {
    styles: ['multi-day', 'adventure', 'sunrise', 'private'],
    badge: 'Overland',
    bestFor: 'Travelers comparing a longer Java route from Jogja to Bromo.',
  },
};

const routeUsdPriceById = {
  'bromo-sunrise': 29,
  'bromo-madakaripura': 43,
  'jogja-heritage': 55,
  'bromo-family-jeep': 35,
  'bromo-camping': 78,
  'bromo-photography': 48,
  'tumpak-sewu-daytrip': 48,
  'tumpak-sewu-lower-trek': 55,
  'tumpak-kapas-biru': 74,
  'tumpak-bromo-2d1n': 82,
  'jogja-prambanan-city': 45,
  'jogja-merapi-jeep': 49,
  'jogja-family-slow': 61,
  'jogja-culinary-night': 27,
  'medan-lake-toba': 139,
  'medan-culinary': 38,
  'medan-berastagi': 82,
  'medan-toba-family': 145,
  'bromo-tumpak': 74,
  'jogja-bromo-overland': 130,
};

const routeReviewById = {
  'bromo-sunrise': { rating: 5, reviewCount: 892 },
  'bromo-madakaripura': { rating: 5, reviewCount: 265 },
  'jogja-heritage': { rating: 5, reviewCount: 341 },
  'bromo-family-jeep': { rating: 5, reviewCount: 214 },
  'bromo-camping': { rating: 5, reviewCount: 118 },
  'bromo-photography': { rating: 5, reviewCount: 176 },
  'tumpak-sewu-daytrip': { rating: 5, reviewCount: 203 },
  'tumpak-sewu-lower-trek': { rating: 5, reviewCount: 146 },
  'tumpak-kapas-biru': { rating: 5, reviewCount: 91 },
  'tumpak-bromo-2d1n': { rating: 5, reviewCount: 188 },
  'jogja-prambanan-city': { rating: 5, reviewCount: 156 },
  'jogja-merapi-jeep': { rating: 5, reviewCount: 224 },
  'jogja-family-slow': { rating: 5, reviewCount: 132 },
  'jogja-culinary-night': { rating: 5, reviewCount: 84 },
  'medan-lake-toba': { rating: 5, reviewCount: 117 },
  'medan-culinary': { rating: 5, reviewCount: 73 },
  'medan-berastagi': { rating: 5, reviewCount: 69 },
  'medan-toba-family': { rating: 5, reviewCount: 102 },
  'bromo-tumpak': { rating: 5, reviewCount: 194 },
  'jogja-bromo-overland': { rating: 5, reviewCount: 58 },
};

const localizedRouteTextById = {
  'bromo-sunrise': {
    title: { id: 'Private Trip Sunrise Bromo', cn: 'Bromo 日出私人行程', us: 'Bromo Sunrise Private Trip' },
    category: { id: 'Alam', cn: '自然', us: 'Nature' },
    tag: { id: 'Best Seller', cn: '畅销', us: 'Best Seller' },
    badge: { id: 'Best seller', cn: '畅销推荐', us: 'Best seller' },
    difficulty: { id: 'Mudah sampai sedang', cn: '简单至中等', us: 'Easy to moderate' },
    highlights: {
      id: ['Viewpoint sunrise', 'Lautan Pasir', 'Area kawah Bromo'],
      cn: ['日出观景点', '沙海', 'Bromo 火山口区域'],
      us: ['Sunrise viewpoint', 'Sea of Sand', 'Bromo crater area'],
    },
    intro: {
      id: 'Rute sunrise Bromo klasik dengan jeep, Lautan Pasir, dan area kawah untuk first timer.',
      cn: '经典 Bromo 日出路线，包含吉普车、沙海和火山口区域，适合第一次来访。',
      us: 'The classic Bromo sunrise, jeep, Sea of Sand, and crater route for first-time East Java travelers.',
    },
    bestFor: {
      id: 'Traveler pertama kali ke Bromo yang ingin sunrise dan rute jeep klasik.',
      cn: '适合第一次去 Bromo、想体验经典日出和吉普车路线的旅客。',
      us: 'First-time Bromo travelers who want the classic sunrise and jeep route.',
    },
  },
  'bromo-madakaripura': {
    title: { id: 'Bromo + Air Terjun Madakaripura', cn: 'Bromo + Madakaripura 瀑布', us: 'Bromo + Madakaripura Waterfall' },
    category: { id: 'Alam', cn: '自然', us: 'Nature' },
    badge: { id: 'Add-on air terjun', cn: '瀑布加选', us: 'Waterfall add-on' },
    difficulty: { id: 'Sedang', cn: '中等', us: 'Moderate' },
    highlights: { id: ['Sunrise Bromo', 'Lautan Pasir', 'Air Terjun Madakaripura'], cn: ['Bromo 日出', '沙海', 'Madakaripura 瀑布'], us: ['Bromo sunrise', 'Sea of Sand', 'Madakaripura waterfall area'] },
    intro: { id: 'Hari penuh Bromo yang menggabungkan sunrise jeep dengan air terjun dekat Probolinggo.', cn: '更完整的一日 Bromo 行程，结合日出吉普车和 Probolinggo 附近瀑布。', us: 'A fuller Bromo day combining the sunrise jeep route with a waterfall stop near Probolinggo.' },
    bestFor: { id: 'Traveler yang ingin sunrise Bromo plus satu stop alam yang lebih kuat.', cn: '适合想要 Bromo 日出再加一个自然亮点的旅客。', us: 'Travelers who want Bromo sunrise plus one stronger nature stop.' },
  },
  'jogja-heritage': {
    title: { id: 'Jogja Heritage & Kuliner', cn: 'Jogja 文化遗产与美食', us: 'Jogja Heritage & Culinary' },
    category: { id: 'Budaya', cn: '文化', us: 'Culture' },
    badge: { id: 'Pilihan budaya', cn: '文化精选', us: 'Culture pick' },
    difficulty: { id: 'Mudah', cn: '简单', us: 'Easy' },
    highlights: { id: ['Area Prambanan', 'Stop kuliner lokal', 'Rute kota Yogyakarta'], cn: ['Prambanan 区域', '当地美食', 'Yogyakarta 城市路线'], us: ['Prambanan heritage area', 'Local culinary stops', 'Yogyakarta city route'] },
    intro: { id: 'Rute budaya untuk candi, cerita kota, makanan lokal, dan tempo private trip yang santai.', cn: '文化路线，包含寺庙、城市故事、当地美食和轻松的私人行程节奏。', us: 'A cultural route for temples, city stories, local food, and a relaxed private-trip pace.' },
    bestFor: { id: 'Grup yang ingin candi, kuliner, dan tempo Jogja yang santai.', cn: '适合想要寺庙、美食和轻松 Jogja 节奏的团队。', us: 'Groups who want temples, food, and a relaxed Jogja pace.' },
  },
  'bromo-family-jeep': {
    title: { id: 'Bromo Family Jeep Easy Route', cn: 'Bromo 家庭轻松吉普路线', us: 'Bromo Family Jeep Easy Route' },
    category: { id: 'Keluarga', cn: '家庭', us: 'Family' },
    badge: { id: 'Family friendly', cn: '适合家庭', us: 'Family friendly' },
    difficulty: { id: 'Mudah', cn: '简单', us: 'Easy' },
    highlights: { id: ['Area sunrise', 'Rute jeep singkat', 'Stop istirahat fleksibel'], cn: ['日出区域', '短程吉普路线', '灵活休息点'], us: ['Sunrise area', 'Short jeep route', 'Flexible rest stops'] },
    intro: { id: 'Itinerary Bromo yang lebih ringan untuk keluarga, senior, dan grup yang ingin minim jalan kaki.', cn: '更轻松的 Bromo 行程，适合家庭、长者和希望少走路的团队。', us: 'A softer Bromo itinerary for families, seniors, and groups who prefer less walking.' },
    bestFor: { id: 'Keluarga dan grup campuran usia yang ingin minim jalan kaki.', cn: '适合家庭和不同年龄组合、希望少走路的团队。', us: 'Families and mixed-age groups who prefer less walking.' },
  },
  'bromo-camping': {
    title: { id: 'Bromo Camping & Stargazing', cn: 'Bromo 露营与观星', us: 'Bromo Camping & Stargazing' },
    category: { id: 'Adventure', cn: '冒险', us: 'Adventure' },
    badge: { id: 'Outdoor', cn: '户外', us: 'Outdoor' },
    bestFor: { id: 'Traveler yang ingin malam gunung sebelum sunrise.', cn: '适合想在日出前体验山地夜晚的旅客。', us: 'Travelers who want a slower mountain night before sunrise.' },
  },
  'bromo-photography': {
    title: { id: 'Bromo Photography Sunrise Route', cn: 'Bromo 日出摄影路线', us: 'Bromo Photography Sunrise Route' },
    category: { id: 'Rute Foto', cn: '摄影路线', us: 'Photo Route' },
    badge: { id: 'Rute foto', cn: '摄影路线', us: 'Photo route' },
    bestFor: { id: 'Traveler yang ingin waktu lebih banyak di viewpoint dan spot foto jeep.', cn: '适合想在观景点和吉普车拍照点停留更久的旅客。', us: 'Travelers who want more time at viewpoints and jeep photo stops.' },
  },
  'tumpak-sewu-daytrip': {
    title: { id: 'Day Trip Air Terjun Tumpak Sewu', cn: 'Tumpak Sewu 瀑布一日游', us: 'Tumpak Sewu Waterfall Day Trip' },
    category: { id: 'Adventure', cn: '冒险', us: 'Adventure' },
    badge: { id: 'Trip air terjun', cn: '瀑布行程', us: 'Waterfall trip' },
    bestFor: { id: 'Traveler yang ingin panorama Tumpak Sewu tanpa menginap.', cn: '适合想看 Tumpak Sewu 全景但不想过夜的旅客。', us: 'Travelers who want the Tumpak Sewu panorama without staying overnight.' },
  },
  'tumpak-sewu-lower-trek': {
    title: { id: 'Tumpak Sewu Lower Trek', cn: 'Tumpak Sewu 下层徒步', us: 'Tumpak Sewu Lower Trek' },
    category: { id: 'Adventure', cn: '冒险', us: 'Adventure' },
    badge: { id: 'Trekking', cn: '徒步', us: 'Trekking' },
    bestFor: { id: 'Traveler aktif yang nyaman dengan jalur basah dan akses lebih curam.', cn: '适合能接受湿滑步道和较陡路线的活跃旅客。', us: 'Active travelers comfortable with wet trails and steeper access.' },
  },
  'tumpak-kapas-biru': {
    title: { id: 'Tumpak Sewu + Kapas Biru', cn: 'Tumpak Sewu + Kapas Biru', us: 'Tumpak Sewu + Kapas Biru' },
    category: { id: 'Rute Foto', cn: '摄影路线', us: 'Photo Route' },
    badge: { id: 'Rute foto', cn: '摄影路线', us: 'Photo route' },
    bestFor: { id: 'Traveler pencinta air terjun yang ingin lebih dari satu stop di Lumajang.', cn: '适合想在 Lumajang 探访多个瀑布点的旅客。', us: 'Waterfall-focused travelers who want more than one Lumajang stop.' },
  },
  'tumpak-bromo-2d1n': {
    title: { id: 'Tumpak Sewu + Bromo 2D1N', cn: 'Tumpak Sewu + Bromo 两天一夜', us: 'Tumpak Sewu + Bromo 2D1N' },
    category: { id: 'Adventure', cn: '冒险', us: 'Adventure' },
    badge: { id: 'Combo', cn: '组合路线', us: 'Combo' },
    bestFor: { id: 'Traveler yang ingin membandingkan waterfall dan volcano dalam dua hari.', cn: '适合想在两天内体验瀑布和火山景观的旅客。', us: 'Travelers comparing waterfall and volcano scenery in two days.' },
  },
  'jogja-prambanan-city': {
    title: { id: 'Jogja Prambanan & City Route', cn: 'Jogja Prambanan 与城市路线', us: 'Jogja Prambanan & City Route' },
    category: { id: 'Budaya', cn: '文化', us: 'Culture' },
    badge: { id: 'Heritage', cn: '文化遗产', us: 'Heritage' },
    bestFor: { id: 'Traveler dengan satu hari untuk Prambanan, kota, dan makanan lokal.', cn: '适合用一天体验 Prambanan、城市景点和当地美食。', us: 'Travelers with one day for Prambanan, city stops, and local food.' },
  },
  'jogja-merapi-jeep': {
    title: { id: 'Jogja Merapi Jeep Adventure', cn: 'Jogja Merapi 吉普冒险', us: 'Jogja Merapi Jeep Adventure' },
    category: { id: 'Adventure', cn: '冒险', us: 'Adventure' },
    badge: { id: 'Jeep', cn: '吉普车', us: 'Jeep' },
    bestFor: { id: 'Traveler Jogja yang ingin outdoor tanpa trekking berat.', cn: '适合想在 Jogja 体验户外但不想重度徒步的旅客。', us: 'Jogja travelers who want an outdoor route without a hard trek.' },
  },
  'jogja-family-slow': {
    title: { id: 'Jogja Family Slow Trip', cn: 'Jogja 家庭慢节奏行程', us: 'Jogja Family Slow Trip' },
    category: { id: 'Keluarga', cn: '家庭', us: 'Family' },
    badge: { id: 'Family friendly', cn: '适合家庭', us: 'Family friendly' },
    bestFor: { id: 'Keluarga yang ingin budaya dan kuliner dengan waktu istirahat fleksibel.', cn: '适合想体验文化和美食、并需要灵活休息时间的家庭。', us: 'Families who want culture and food with flexible rest time.' },
  },
  'jogja-culinary-night': {
    title: { id: 'Jogja Culinary Night Route', cn: 'Jogja 夜间美食路线', us: 'Jogja Culinary Night Route' },
    category: { id: 'Kuliner', cn: '美食', us: 'Culinary' },
    badge: { id: 'Kuliner', cn: '美食', us: 'Culinary' },
    bestFor: { id: 'Traveler singkat yang mencari rute malam yang ringan.', cn: '适合停留时间短、想体验轻松夜间路线的旅客。', us: 'Short-stay travelers looking for an easy evening route.' },
  },
  'medan-lake-toba': {
    title: { id: 'Medan + Lake Toba 3D2N', cn: 'Medan + Lake Toba 三天两夜', us: 'Medan + Lake Toba 3D2N' },
    category: { id: 'Alam', cn: '自然', us: 'Nature' },
    badge: { id: 'Lake Toba', cn: 'Lake Toba', us: 'Lake Toba' },
    bestFor: { id: 'Traveler yang tiba di Medan dan ingin rute Lake Toba yang tertata.', cn: '适合抵达 Medan 后想安排 Lake Toba 路线的旅客。', us: 'Travelers arriving in Medan who want an organized Lake Toba route.' },
  },
  'medan-culinary': {
    title: { id: 'Medan Culinary Short Escape', cn: 'Medan 短途美食路线', us: 'Medan Culinary Short Escape' },
    category: { id: 'Kuliner', cn: '美食', us: 'Culinary' },
    badge: { id: 'Kuliner', cn: '美食', us: 'Culinary' },
    bestFor: { id: 'Traveler singkat yang ingin kuliner Medan dan stop kota.', cn: '适合短暂停留、想体验 Medan 美食和城市点位的旅客。', us: 'Short-stay travelers who want Medan food and city stops.' },
  },
  'medan-berastagi': {
    title: { id: 'Medan + Berastagi Highland', cn: 'Medan + Berastagi 高地', us: 'Medan + Berastagi Highland' },
    category: { id: 'Alam', cn: '自然', us: 'Nature' },
    badge: { id: 'Highland', cn: '高地', us: 'Highland' },
    bestFor: { id: 'Grup yang ingin udara sejuk dan pemandangan highland dekat Medan.', cn: '适合想体验 Medan 附近凉爽高地风景的团队。', us: 'Groups who want cooler highland scenery near Medan.' },
  },
  'medan-toba-family': {
    title: { id: 'Lake Toba Family Scenic Route', cn: 'Lake Toba 家庭风景路线', us: 'Lake Toba Family Scenic Route' },
    category: { id: 'Keluarga', cn: '家庭', us: 'Family' },
    badge: { id: 'Rute keluarga', cn: '家庭路线', us: 'Family route' },
    bestFor: { id: 'Keluarga yang ingin Lake Toba dengan hari perjalanan yang manageable.', cn: '适合想轻松安排 Lake Toba 行程的家庭。', us: 'Families who want Lake Toba with manageable travel days.' },
  },
  'bromo-tumpak': {
    title: { id: 'Bromo + Tumpak Sewu 2D1N', cn: 'Bromo + Tumpak Sewu 两天一夜', us: 'Bromo + Tumpak Sewu 2D1N' },
    category: { id: 'Adventure', cn: '冒险', us: 'Adventure' },
    badge: { id: 'Rekomendasi', cn: '推荐', us: 'Recommended' },
    bestFor: { id: 'Traveler yang ingin sunrise Bromo dan Tumpak Sewu dalam satu plan East Java.', cn: '适合想在一个东爪哇计划中体验 Bromo 日出和 Tumpak Sewu 的旅客。', us: 'Travelers who want Bromo sunrise and Tumpak Sewu in one East Java plan.' },
  },
  'jogja-bromo-overland': {
    title: { id: 'Preview Overland Jogja ke Bromo', cn: 'Jogja 到 Bromo 陆路预览', us: 'Jogja to Bromo Overland Preview' },
    category: { id: 'Overland', cn: '陆路组合', us: 'Overland' },
    badge: { id: 'Overland', cn: '陆路组合', us: 'Overland' },
    bestFor: { id: 'Traveler yang membandingkan rute Java lebih panjang dari Jogja ke Bromo.', cn: '适合比较从 Jogja 到 Bromo 的较长 Java 路线。', us: 'Travelers comparing a longer Java route from Jogja to Bromo.' },
  },
};

function createRoute(route) {
  const destination = getDestinationById(route.destinationId);
  const destinationName = destination?.name ?? route.destinationName;
  const pickupAreas = route.pickupAreas ?? ['Hotel area', 'Airport', 'Train station'];
  const highlights = route.highlights ?? [];
  const difficulty = route.difficulty ?? 'Easy';
  const discovery = routeDiscoveryById[route.id] ?? {};
  const localizedText = localizedRouteTextById[route.id] ?? {};
  const review = route.review ?? routeReviewById[route.id] ?? { rating: 5, reviewCount: 0 };

  return {
    featured: false,
    sortOrder: 99,
    destinationId: route.destinationId,
    destinationName,
    destination: destinationName,
    basePriceIdr: route.basePriceIdr ?? route.basePrice,
    basePriceUsd: route.basePriceUsd ?? routeUsdPriceById[route.id],
    image: routeImageById[route.id] ?? route.image ?? destinationFallbackImage[route.destinationId],
    imageAlt: route.imageAlt ?? routeImageAltById[route.id] ?? `${destinationName} route package image`,
    imageCredit: route.imageCredit ?? imageCreditByDestination[route.destinationId],
    sourceRefs: route.sourceRefs ?? sourceRefsByDestination[route.destinationId] ?? [],
    pickupAreas,
    pickupLabel: route.pickupLabel ?? { id: 'Pickup tersedia', cn: '可接送', us: 'Pickup available' },
    groupType: route.groupType ?? { id: 'Private trip', cn: '私人行程', us: 'Private trip' },
    gallery: route.gallery ?? [
      routeImageById[route.id] ?? route.image ?? destinationFallbackImage[route.destinationId],
      destinationFallbackImage[route.destinationId],
    ].filter(Boolean),
    packageOptions: route.packageOptions ?? [
      {
        id: `${route.id}-private`,
        title: route.title,
        description: route.intro,
        duration: route.duration,
        groupType: route.groupType ?? { id: 'Private trip', cn: '私人行程', us: 'Private trip' },
        pickupLabel: route.pickupLabel ?? { id: 'Pickup tersedia', cn: '可接送', us: 'Pickup available' },
        basePriceIdr: route.basePriceIdr ?? route.basePrice,
        basePriceUsd: route.basePriceUsd ?? routeUsdPriceById[route.id],
      },
    ],
    pickupDetails: route.pickupDetails ?? [
      { id: `Area pickup: ${pickupAreas.join(', ')}`, cn: `接送区域：${pickupAreas.join(', ')}`, us: `Pickup areas: ${pickupAreas.join(', ')}` },
      {
        id: 'Detail titik jemput dikonfirmasi lewat WhatsApp setelah tanggal dipilih.',
        cn: '选择日期后，将通过 WhatsApp 确认详细接送点。',
        us: 'Exact pickup point is confirmed via WhatsApp after the date is selected.',
      },
    ],
    goodToKnow: route.goodToKnow ?? defaultGoodToKnow,
    policies: route.policies ?? defaultPackagePolicies,
    testimonials: route.testimonials ?? [],
    rating: review.rating,
    reviewCount: review.reviewCount,
    reviewSource: route.reviewSource ?? { id: 'Traveler reviews', cn: '旅客评价', us: 'Traveler reviews' },
    reviewSummary: route.reviewSummary ?? {
      id: 'Rating preview berdasarkan testimoni dan catatan traveler.',
      cn: '基于旅客反馈和评价的评分预览。',
      us: 'Rating preview based on traveler feedback and notes.',
    },
    addOns: route.addOns ?? routeAddOnsByDestination[route.destinationId] ?? [],
    operator: route.operator ?? { id: 'Tinggal Jalan local team', cn: 'Tinggal Jalan 当地团队', us: 'Tinggal Jalan local team' },
    badge: route.badge ?? discovery.badge ?? route.tag,
    bestFor: route.bestFor ?? discovery.bestFor ?? route.why,
    styles: route.styles ?? discovery.styles ?? ['private'],
    highlights,
    difficulty,
    priceNote: PRICE_NOTE,
    itinerary: route.itinerary ?? [
      {
        id: 'Pickup dari meeting point yang dipilih',
        cn: '从所选集合点接送',
        us: 'Pickup from selected meeting point',
      },
      ...highlights.slice(0, 3).map((highlight) => ({
        id: `Kunjungi ${typeof highlight === 'object' ? highlight.id : highlight}`,
        cn: `游览 ${typeof highlight === 'object' ? highlight.cn : highlight}`,
        us: `Visit ${typeof highlight === 'object' ? highlight.us : highlight}`,
      })),
      {
        id: 'Transfer pulang atau drop-off di titik pilihan',
        cn: '返回或送到所选地点',
        us: 'Return transfer or drop-off at selected point',
      },
    ],
    details: route.details ?? [
      { id: `Destinasi: ${destinationName}`, cn: `目的地：${destinationName}`, us: `Destination: ${destinationName}` },
      { id: `Pickup: ${pickupAreas.join(', ')}`, cn: `接送：${pickupAreas.join(', ')}`, us: `Pickup: ${pickupAreas.join(', ')}` },
      { id: 'Transport: Private car dengan driver lokal', cn: '交通：当地司机私人车辆', us: 'Transport: Private car with local driver' },
      { id: `Tingkat kesulitan: ${typeof difficulty === 'object' ? difficulty.id : difficulty}`, cn: `难度：${typeof difficulty === 'object' ? difficulty.cn : difficulty}`, us: `Difficulty: ${typeof difficulty === 'object' ? difficulty.us : difficulty}` },
    ],
    includes: route.includes ?? [
      { id: 'Transport private', cn: '私人交通', us: 'Private transport' },
      { id: 'Driver', cn: '司机', us: 'Driver' },
      { id: 'Perencanaan rute', cn: '路线规划', us: 'Route planning' },
      { id: 'Support WhatsApp', cn: 'WhatsApp 支持', us: 'WhatsApp support' },
    ],
    excludes: route.excludes ?? [
      { id: 'Tiket masuk', cn: '门票', us: 'Entrance tickets' },
      { id: 'Makan', cn: '餐食', us: 'Meals' },
      { id: 'Pengeluaran pribadi', cn: '个人消费', us: 'Personal expenses' },
    ],
    notes: route.notes ?? [
      {
        id: 'Estimasi harga; konfirmasi harga operasional final setelah jadwal dan kebutuhan grup dicek.',
        cn: '预估价格；确认日期和团队需求后提供最终运营价格。',
        us: PRICE_NOTE,
      },
      {
        id: 'Jadwal bisa disesuaikan dengan waktu pesawat atau kereta.',
        cn: '时间可根据航班或火车时间调整。',
        us: 'Schedule can be adjusted around flight or train timing.',
      },
      {
        id: 'Ketersediaan dapat berubah karena regulasi lokal, cuaca, atau periode upacara.',
        cn: '可用性可能因当地规定、天气或仪式期间而变化。',
        us: 'Availability can change during local regulation, weather, or ceremony periods.',
      },
    ],
    ...route,
    title: localizedText.title ?? route.title,
    category: localizedText.category ?? route.category,
    tag: localizedText.tag ?? route.tag,
    pickupLabel: route.pickupLabel ?? { id: 'Pickup tersedia', cn: '可接送', us: 'Pickup available' },
    groupType: route.groupType ?? { id: 'Private trip', cn: '私人行程', us: 'Private trip' },
    badge: route.badge ?? localizedText.badge ?? discovery.badge ?? route.tag,
    bestFor: route.bestFor ?? localizedText.bestFor ?? discovery.bestFor ?? route.why,
    intro: localizedText.intro ?? route.intro,
    why: localizedText.why ?? localizedText.intro ?? route.why,
    difficulty: localizedText.difficulty ?? difficulty,
    highlights: localizedText.highlights ?? highlights,
    destinationName,
    destination: destinationName,
  };
}

export const routeArticles = [
  createRoute({
    id: 'bromo-sunrise',
    title: 'Bromo Sunrise Private Trip',
    destinationId: 'bromo',
    category: 'Nature',
    tag: 'Best Seller',
    featured: true,
    sortOrder: 1,
    duration: '1 Day',
    basePrice: 350000,
    difficulty: 'Easy to moderate',
    pickupAreas: ['Malang hotel', 'Surabaya airport', 'Train station'],
    highlights: ['Sunrise viewpoint', 'Sea of Sand', 'Bromo crater area'],
    intro: 'The classic Bromo sunrise, jeep, Sea of Sand, and crater route for first-time East Java travelers.',
    why: 'Bromo is known for sunrise viewpoints, volcanic sand plains, and crater access that work well in a compact private trip.',
    gallery: [
      '/images/routes/unique/bromo-sunrise.jpg',
      '/images/routes/bromo-jeep.jpg',
      '/images/destinations/bromo.jpg',
    ],
    packageOptions: [
      {
        id: 'bromo-sunrise-private',
        title: { id: 'Private Bromo Sunrise Trip', cn: 'Bromo 日出私人行程', us: 'Private Bromo Sunrise Trip' },
        description: {
          id: 'Jeep private untuk sunrise, Lautan Pasir, dan area kawah dengan pickup fleksibel dari Malang atau Surabaya.',
          cn: '私人吉普车日出行程，包含沙海和火山口区域，可从 Malang 或 Surabaya 灵活接送。',
          us: 'Private jeep for sunrise, the Sea of Sand, and crater area with flexible pickup from Malang or Surabaya.',
        },
        duration: '1 Day',
        groupType: { id: 'Private trip', cn: '私人行程', us: 'Private trip' },
        pickupLabel: { id: 'Pickup Malang / Surabaya', cn: 'Malang / Surabaya 接送', us: 'Malang / Surabaya pickup' },
        basePriceIdr: 350000,
        basePriceUsd: 29,
      },
    ],
    itinerary: [
      { id: '00:30 - Pickup dari area Malang atau Surabaya', cn: '00:30 - 从 Malang 或 Surabaya 区域接送', us: '00:30 - Pickup from Malang or Surabaya area' },
      { id: '03:30 - Transfer jeep ke viewpoint sunrise', cn: '03:30 - 乘吉普车前往日出观景点', us: '03:30 - Jeep transfer to sunrise viewpoint' },
      { id: '05:00 - Sesi sunrise dan stop minuman hangat', cn: '05:00 - 日出观赏与热饮休息', us: '05:00 - Sunrise session and warm drink stop' },
      { id: '06:30 - Lautan Pasir dan area kawah Bromo', cn: '06:30 - 沙海和 Bromo 火山口区域', us: '06:30 - Sea of Sand and Bromo crater area' },
      { id: '09:30 - Transfer kembali ke kota pickup', cn: '09:30 - 返回接送城市', us: '09:30 - Return transfer to pickup city' },
    ],
    details: [
      { id: 'Durasi: 1 hari, berangkat tengah malam', cn: '时长：1天，午夜出发', us: 'Duration: 1 day, midnight departure' },
      { id: 'Area pickup: hotel Malang, bandara Surabaya, atau stasiun', cn: '接送区域：Malang 酒店、Surabaya 机场或火车站', us: 'Pickup areas: Malang hotel, Surabaya airport, or train station' },
      { id: 'Transport: mobil private dan jeep 4x4 di area Bromo', cn: '交通：私人车辆和 Bromo 区域 4x4 吉普车', us: 'Transport: private car and 4x4 jeep in Bromo area' },
      { id: 'Tingkat kesulitan: mudah sampai sedang', cn: '难度：简单至中等', us: 'Difficulty: easy to moderate' },
    ],
    includes: [
      { id: 'Transport private', cn: '私人交通', us: 'Private transport' },
      { id: 'Jeep 4x4 Bromo', cn: 'Bromo 4x4 吉普车', us: 'Bromo 4x4 jeep' },
      { id: 'Driver lokal', cn: '当地司机', us: 'Local driver' },
      { id: 'Dokumentasi dasar', cn: '基础拍摄记录', us: 'Basic documentation' },
      { id: 'Support WhatsApp sebelum dan saat trip', cn: '行程前和当天 WhatsApp 支持', us: 'WhatsApp support before and during the trip' },
    ],
    excludes: [
      { id: 'Tiket masuk kawasan Bromo', cn: 'Bromo 景区门票', us: 'Bromo entrance ticket' },
      { id: 'Makan dan minuman di luar yang disebutkan', cn: '未注明的餐饮', us: 'Meals and drinks unless stated' },
      { id: 'Sewa kuda opsional menuju tangga kawah', cn: '前往火山口台阶的可选骑马费用', us: 'Optional horse ride to the crater stairs' },
      { id: 'Pengeluaran pribadi dan tip', cn: '个人消费和小费', us: 'Personal expenses and tips' },
    ],
    pickupDetails: [
      { id: 'Pickup utama dari hotel Malang, Bandara Juanda Surabaya, Stasiun Surabaya, atau titik kota yang disepakati.', cn: '主要接送点包括 Malang 酒店、Surabaya Juanda 机场、Surabaya 火车站或约定市区地点。', us: 'Main pickup from Malang hotels, Surabaya Juanda Airport, Surabaya train station, or an agreed city point.' },
      { id: 'Jam pickup final mengikuti lokasi menginap, kondisi jalan, dan target viewpoint sunrise.', cn: '最终接送时间会根据住宿位置、路况和日出观景点目标确认。', us: 'Final pickup time depends on stay location, road conditions, and the target sunrise viewpoint.' },
      { id: 'Drop-off kembali ke kota pickup atau titik lain yang masih searah, dikonfirmasi sebelum berangkat.', cn: '可送回接送城市或顺路地点，出发前确认。', us: 'Drop-off returns to the pickup city or another aligned point, confirmed before departure.' },
    ],
    goodToKnow: [
      { id: 'Suhu dini hari di viewpoint bisa dingin; bawa jaket, kaus kaki, dan sepatu yang nyaman.', cn: '日出观景点凌晨较冷；建议带外套、袜子和舒适鞋子。', us: 'Early morning at the viewpoint can be cold; bring a jacket, socks, and comfortable shoes.' },
      { id: 'Akses kawah membutuhkan jalan kaki dan tangga; traveler bisa menunggu di area jeep bila tidak ingin naik.', cn: '火山口区域需要步行和爬楼梯；不想上去的旅客可在吉普车区域等待。', us: 'Crater access requires walking and stairs; travelers can wait near the jeep area if they prefer not to climb.' },
      { id: 'View sunrise bergantung cuaca dan kabut, jadi tim akan memilih viewpoint yang paling masuk akal saat hari H.', cn: '日出视野取决于天气和雾况，团队会在当天选择最合适的观景点。', us: 'Sunrise visibility depends on weather and fog, so the team chooses the most practical viewpoint on the travel day.' },
      { id: 'Tiket masuk biasanya dibayar terpisah agar harga bisa mengikuti status weekday, weekend, lokal, atau internasional.', cn: '门票通常单独支付，以便根据平日、周末、本地或国际旅客身份调整。', us: 'Entrance tickets are usually paid separately so pricing can follow weekday, weekend, local, or international status.' },
    ],
    policies: {
      cancellation: {
        id: 'Bisa batal gratis sampai 24 jam sebelum pickup setelah jadwal final dikonfirmasi. Perubahan karena cuaca atau regulasi lokal akan dibicarakan lewat WhatsApp.',
        cn: '最终行程确认后，接送前24小时以前可免费取消。因天气或当地规定产生的调整将通过 WhatsApp 沟通。',
        us: 'Free cancellation up to 24 hours before pickup after the final schedule is confirmed. Weather or local-regulation changes are discussed via WhatsApp.',
      },
      confirmation: {
        id: 'Tim mengonfirmasi driver, jeep, titik pickup, estimasi tiket, dan harga final sebelum pembayaran.',
        cn: '付款前，团队会确认司机、吉普车、接送点、门票预估和最终价格。',
        us: 'The team confirms driver, jeep, pickup point, ticket estimate, and final price before payment.',
      },
    },
    testimonials: [
      {
        name: 'First-time Bromo traveler',
        meta: { id: 'Catatan preview', cn: '预览反馈', us: 'Preview note' },
        quote: {
          id: 'Pickup tengah malam terasa lebih aman karena driver sudah update lokasi dan rundown sebelum berangkat.',
          cn: '午夜接送更安心，因为司机出发前已经更新位置和行程安排。',
          us: 'The midnight pickup felt safer because the driver shared the location and rundown before departure.',
        },
      },
      {
        name: 'Family group',
        meta: { id: 'Catatan preview', cn: '预览反馈', us: 'Preview note' },
        quote: {
          id: 'Rute bisa dibuat lebih santai saat ada keluarga yang tidak ingin naik sampai kawah.',
          cn: '如果家人不想走到火山口，路线可以安排得更轻松。',
          us: 'The route can be paced more gently when some family members do not want to climb to the crater.',
        },
      },
    ],
  }),
  createRoute({
    id: 'bromo-madakaripura',
    title: 'Bromo + Madakaripura Waterfall',
    destinationId: 'bromo',
    category: 'Nature',
    tag: 'Waterfall Add-on',
    featured: true,
    sortOrder: 2,
    duration: '1 Day',
    basePrice: 525000,
    difficulty: 'Moderate',
    highlights: ['Bromo sunrise', 'Sea of Sand', 'Madakaripura waterfall area'],
    intro: 'A fuller Bromo day combining the sunrise jeep route with a waterfall stop near Probolinggo.',
    why: 'This route fits travelers who want Bromo’s volcanic scenery plus a refreshing canyon-style waterfall extension.',
  }),
  createRoute({
    id: 'jogja-heritage',
    title: 'Jogja Heritage & Culinary',
    destinationId: 'jogja',
    category: 'Culture',
    tag: 'Culture Trip',
    featured: true,
    sortOrder: 3,
    duration: '2D1N',
    basePrice: 650000,
    pickupAreas: ['Yogyakarta airport', 'Train station', 'Hotel'],
    highlights: ['Prambanan heritage area', 'Local culinary stops', 'Yogyakarta city route'],
    intro: 'A cultural route for temples, city stories, local food, and a relaxed private-trip pace.',
    why: 'Yogyakarta is one of Java’s strongest culture bases, with temple heritage and city experiences that suit families and groups.',
    itinerary: [
      'Day 1 - Airport or station pickup and city orientation',
      'Day 1 - Heritage site visit and local dinner',
      'Day 2 - Temple visit, culinary stop, and shopping area',
      'Day 2 - Drop-off at hotel, station, or airport',
    ],
  }),
  createRoute({
    id: 'bromo-family-jeep',
    title: 'Bromo Family Jeep Easy Route',
    destinationId: 'bromo',
    category: 'Family',
    tag: 'Family',
    sortOrder: 4,
    duration: '1 Day',
    basePrice: 425000,
    difficulty: 'Easy',
    highlights: ['Sunrise area', 'Short jeep route', 'Flexible rest stops'],
    intro: 'A softer Bromo itinerary for families, seniors, and groups who prefer less walking.',
    why: 'This package keeps the iconic jeep experience but allows more rest time and simpler movement.',
  }),
  createRoute({
    id: 'bromo-camping',
    title: 'Bromo Camping & Stargazing',
    destinationId: 'bromo',
    category: 'Adventure',
    tag: 'Outdoor',
    sortOrder: 5,
    duration: '2D1N',
    basePrice: 950000,
    difficulty: 'Moderate',
    highlights: ['Highland camping', 'Stargazing', 'Sunrise jeep route'],
    intro: 'An outdoor Bromo route with a mountain night before the sunrise jeep experience.',
    why: 'The route is designed for travelers who want a slower, more immersive Bromo highland experience.',
  }),
  createRoute({
    id: 'bromo-photography',
    title: 'Bromo Photography Sunrise Route',
    destinationId: 'bromo',
    category: 'Photo Route',
    tag: 'Photo Route',
    sortOrder: 6,
    duration: '1 Day',
    basePrice: 575000,
    difficulty: 'Easy to moderate',
    highlights: ['Sunrise viewpoint', 'Jeep photo spots', 'Sea of Sand composition stops'],
    intro: 'A Bromo route paced for travelers who want more photo time at sunrise and jeep viewpoints.',
    why: 'Bromo’s open volcanic landscape gives multiple photo moments when the itinerary leaves enough buffer time.',
  }),
  createRoute({
    id: 'tumpak-sewu-daytrip',
    title: 'Tumpak Sewu Waterfall Day Trip',
    destinationId: 'tumpak-sewu',
    category: 'Adventure',
    tag: 'Adventure',
    sortOrder: 7,
    duration: '1 Day',
    basePrice: 575000,
    difficulty: 'Moderate to hard',
    highlights: ['Panorama viewpoint', 'Pronojiwo waterfall area', 'Optional lower trek'],
    intro: 'A focused waterfall route for panorama views and optional lower waterfall trekking.',
    why: 'Tumpak Sewu is known for its dramatic tiered waterfall landscape around the Lumajang and Pronojiwo area.',
  }),
  createRoute({
    id: 'tumpak-sewu-lower-trek',
    title: 'Tumpak Sewu Lower Trek',
    destinationId: 'tumpak-sewu',
    category: 'Adventure',
    tag: 'Trekking',
    sortOrder: 8,
    duration: '1 Day',
    basePrice: 650000,
    difficulty: 'Hard',
    highlights: ['Waterfall base trail', 'Wet canyon path', 'Local guide support'],
    intro: 'A more physical Tumpak Sewu route built around the lower trail and waterfall base experience.',
    why: 'This option is better for active travelers who are comfortable with wet paths, stairs, and trail conditions.',
  }),
  createRoute({
    id: 'tumpak-kapas-biru',
    title: 'Tumpak Sewu + Kapas Biru',
    destinationId: 'tumpak-sewu',
    category: 'Photo Route',
    tag: 'Photo Route',
    sortOrder: 9,
    duration: '2D1N',
    basePrice: 890000,
    difficulty: 'Hard',
    highlights: ['Tumpak Sewu panorama', 'Kapas Biru waterfall', 'Lumajang nature route'],
    intro: 'A waterfall-focused route combining two dramatic East Java landscapes in one organized itinerary.',
    why: 'This route helps waterfall-focused travelers compare multiple Lumajang nature stops without planning transfers alone.',
  }),
  createRoute({
    id: 'tumpak-bromo-2d1n',
    title: 'Tumpak Sewu + Bromo 2D1N',
    destinationId: 'tumpak-sewu',
    category: 'Adventure',
    tag: 'Combo',
    sortOrder: 10,
    image: '/images/routes/tumpak-sewu.jpg',
    duration: '2D1N',
    basePrice: 975000,
    difficulty: 'Moderate to hard',
    highlights: ['Tumpak Sewu panorama', 'Bromo sunrise', 'Private East Java transfer'],
    intro: 'A compact East Java combo for travelers who want waterfall and volcano scenery in two days.',
    why: 'This package connects two strong East Java highlights while keeping pickup and transfer planning simple.',
    sourceRefs: [...sourceRefsByDestination['tumpak-sewu'], ...sourceRefsByDestination.bromo],
  }),
  createRoute({
    id: 'jogja-prambanan-city',
    title: 'Jogja Prambanan & City Route',
    destinationId: 'jogja',
    category: 'Culture',
    tag: 'Heritage',
    sortOrder: 11,
    duration: '1 Day',
    basePrice: 540000,
    highlights: ['Prambanan area', 'Yogyakarta city route', 'Local food stop'],
    intro: 'A one-day Jogja route centered on Prambanan heritage, city movement, and culinary stops.',
    why: 'Prambanan is a major heritage anchor near Yogyakarta and pairs well with a flexible city route.',
  }),
  createRoute({
    id: 'jogja-merapi-jeep',
    title: 'Jogja Merapi Jeep Adventure',
    destinationId: 'jogja',
    category: 'Adventure',
    tag: 'Jeep',
    sortOrder: 12,
    duration: '1 Day',
    basePrice: 580000,
    difficulty: 'Easy to moderate',
    highlights: ['Merapi jeep route', 'Volcanic landscape stops', 'Jogja culinary option'],
    intro: 'A private Jogja route with Merapi jeep trail, local stories, and optional city culinary stops.',
    why: 'Merapi adds an outdoor layer to a Jogja trip without requiring a difficult trek.',
  }),
  createRoute({
    id: 'jogja-family-slow',
    title: 'Jogja Family Slow Trip',
    destinationId: 'jogja',
    category: 'Family',
    tag: 'Family',
    sortOrder: 13,
    duration: '2D1N',
    basePrice: 720000,
    difficulty: 'Easy',
    highlights: ['Family-friendly city stops', 'Flexible temple timing', 'Shopping and food areas'],
    intro: 'A slower Jogja package for family groups who want culture, food, and flexible rest time.',
    why: 'The route keeps the day practical for mixed-age groups while still covering heritage and city atmosphere.',
  }),
  createRoute({
    id: 'jogja-culinary-night',
    title: 'Jogja Culinary Night Route',
    destinationId: 'jogja',
    category: 'Culinary',
    tag: 'Culinary',
    sortOrder: 14,
    duration: 'Half Day',
    basePrice: 325000,
    highlights: ['Gudeg-style food stop', 'Night city route', 'Local snack areas'],
    intro: 'A half-day Jogja food route for travelers with limited time after arrival.',
    why: 'Jogja’s food scene works well as a lighter package when travelers do not need a full-day route.',
  }),
  createRoute({
    id: 'medan-lake-toba',
    title: 'Medan + Lake Toba 3D2N',
    destinationId: 'medan',
    category: 'Nature',
    tag: 'Lake Toba',
    sortOrder: 15,
    duration: '3D2N',
    basePrice: 1650000,
    difficulty: 'Easy to moderate',
    highlights: ['Lake Toba viewpoint', 'Samosir island area', 'Private North Sumatra transfer'],
    intro: 'A North Sumatra route from Medan toward Lake Toba with viewpoints, local food, and organized transfers.',
    why: 'Medan is a common arrival city for Lake Toba access, and this route simplifies the overland movement.',
  }),
  createRoute({
    id: 'medan-culinary',
    title: 'Medan Culinary Short Escape',
    destinationId: 'medan',
    category: 'Culinary',
    tag: 'Culinary',
    sortOrder: 16,
    duration: '1 Day',
    basePrice: 450000,
    highlights: ['Medan city food route', 'Heritage neighborhood', 'Airport-friendly timing'],
    intro: 'A Medan city route focused on local food, heritage neighborhoods, and flexible airport timing.',
    why: 'This package works for short-stay travelers who want Medan’s food culture without leaving the city too far.',
  }),
  createRoute({
    id: 'medan-berastagi',
    title: 'Medan + Berastagi Highland',
    destinationId: 'medan',
    category: 'Nature',
    tag: 'Highland',
    sortOrder: 17,
    duration: '2D1N',
    basePrice: 980000,
    difficulty: 'Easy to moderate',
    highlights: ['Berastagi highland', 'Fruit market area', 'North Sumatra viewpoint'],
    intro: 'A highland route from Medan toward Berastagi for cooler weather, local stops, and viewpoint scenery.',
    why: 'Berastagi gives North Sumatra travelers a mountain route alternative before or after Lake Toba.',
  }),
  createRoute({
    id: 'medan-toba-family',
    title: 'Lake Toba Family Scenic Route',
    destinationId: 'medan',
    category: 'Family',
    tag: 'Family',
    sortOrder: 18,
    duration: '3D2N',
    basePrice: 1725000,
    difficulty: 'Easy',
    highlights: ['Lake Toba scenic stops', 'Family pacing', 'Simple hotel transfer flow'],
    intro: 'A Lake Toba route paced for families who want scenic stops without overloading the travel days.',
    why: 'The package keeps travel manageable for family groups while still giving enough time around Lake Toba.',
  }),
  createRoute({
    id: 'bromo-tumpak',
    title: 'Bromo + Tumpak Sewu 2D1N',
    destinationId: 'bromo',
    category: 'Adventure',
    tag: 'Recommended',
    sortOrder: 19,
    image: '/images/routes/tumpak-sewu.jpg',
    duration: '2D1N',
    basePrice: 875000,
    difficulty: 'Moderate',
    pickupAreas: ['Malang city area'],
    highlights: ['Bromo sunrise', 'Tumpak Sewu panorama', 'Private East Java transfer'],
    intro: 'A two-day adventure combining Bromo sunrise with the dramatic waterfall landscape of Tumpak Sewu.',
    why: 'This package works for travelers who want a fuller East Java itinerary with both volcano and waterfall moments.',
    itinerary: [
      'Day 1 - Pickup, transfer to Bromo area, check-in and rest',
      'Day 2 03:00 - Bromo sunrise and jeep route',
      'Day 2 10:00 - Transfer to Tumpak Sewu panorama area',
      'Day 2 13:00 - Waterfall viewpoint and optional short trek',
      'Day 2 17:00 - Return to Malang',
    ],
    sourceRefs: [...sourceRefsByDestination.bromo, ...sourceRefsByDestination['tumpak-sewu']],
  }),
  createRoute({
    id: 'jogja-bromo-overland',
    title: 'Jogja to Bromo Overland Preview',
    destinationId: 'jogja',
    category: 'Overland',
    tag: 'Overland',
    sortOrder: 20,
    image: '/images/routes/bromo-jeep.jpg',
    duration: '3D2N',
    basePrice: 1550000,
    difficulty: 'Moderate',
    highlights: ['Yogyakarta departure', 'East Java transfer', 'Bromo sunrise'],
    intro: 'An overland package connecting Jogja and Bromo for travelers comparing multi-city Java routes.',
    why: 'This package tests catalog support for cross-destination routes while keeping Jogja as the filter entry point.',
    sourceRefs: [...sourceRefsByDestination.jogja, ...sourceRefsByDestination.bromo],
  }),
];

export const routeDestinations = destinations.map((destination) => ({
  label: destination.name,
  value: destination.id,
}));

export const routeCategories = [...new Set(routeArticles.map((route) => (typeof route.category === 'object' ? route.category.us : route.category)))];

export const routeStyleOptions = [
  { label: { id: 'Rekomendasi', cn: '推荐', us: 'Recommended' }, value: 'recommended' },
  { label: { id: 'Sunrise', cn: '日出', us: 'Sunrise' }, value: 'sunrise' },
  { label: { id: 'Keluarga', cn: '家庭', us: 'Family' }, value: 'family' },
  { label: { id: 'Adventure', cn: '冒险', us: 'Adventure' }, value: 'adventure' },
  { label: { id: 'Budaya', cn: '文化', us: 'Culture' }, value: 'culture' },
  { label: { id: 'Air Terjun', cn: '瀑布', us: 'Waterfall' }, value: 'waterfall' },
  { label: { id: 'Multi-day', cn: '多日', us: 'Multi-day' }, value: 'multi-day' },
  { label: { id: 'Private', cn: '私人团', us: 'Private' }, value: 'private' },
];

export function getFeaturedRoutes() {
  return routeArticles
    .filter((route) => route.featured)
    .sort((first, second) => first.sortOrder - second.sortOrder)
    .slice(0, 3);
}

export const featuredRoutes = getFeaturedRoutes();

export function getRouteById(routeId) {
  return routeArticles.find((route) => route.id === routeId);
}

export function getRoutesByDestinationId(destinationId) {
  return routeArticles.filter((route) => route.destinationId === destinationId);
}

export function getFilteredRoutes({ search = '', destination = 'all', category = 'all', style = 'recommended', sort = 'recommended' } = {}) {
  const normalizedSearch = search.trim().toLowerCase();

  return routeArticles
    .filter((route) => {
      const matchesSearch = normalizedSearch
        ? [route.title, route.destinationName, route.category, route.tag, route.badge, route.bestFor, ...route.highlights]
            .flatMap(getSearchValues)
            .some((value) => value.toLowerCase().includes(normalizedSearch))
        : true;
      const matchesDestination =
        destination === 'all' || route.destinationId === destination || route.destinationName === destination;
      const matchesCategory = category === 'all' || route.category === category;
      const matchesStyle = style === 'all' || style === 'recommended' || route.styles.includes(style);

      return matchesSearch && matchesDestination && matchesCategory && matchesStyle;
    })
    .sort((first, second) => {
      if (sort === 'price-asc') {
        return first.basePrice - second.basePrice;
      }

      if (sort === 'duration') {
        return getDurationDays(first.duration) - getDurationDays(second.duration);
      }

      return first.sortOrder - second.sortOrder;
    });
}

function getSearchValues(value) {
  if (Array.isArray(value)) {
    return value.flatMap(getSearchValues);
  }

  if (value && typeof value === 'object') {
    return Object.values(value).flatMap(getSearchValues);
  }

  return value ? [String(value)] : [];
}

function getDurationDays(duration) {
  if (duration === 'Half Day') {
    return 0.5;
  }

  const dayMatch = duration.match(/^(\d+)D/);

  if (dayMatch) {
    return Number(dayMatch[1]);
  }

  return duration.includes('1 Day') ? 1 : 99;
}
