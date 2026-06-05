export const destinations = [
  {
    id: 'bromo',
    name: 'Bromo',
    region: 'East Java',
    province: 'East Java',
    image: '/images/destinations/bromo.jpg',
    copy: {
      id: 'Viewpoint sunrise, Lautan Pasir, rute jeep, area kawah, dan lanskap dataran tinggi Tengger.',
      cn: '日出观景点、沙海、吉普车路线、火山口区域和 Tengger 高地风景。',
      us: 'Sunrise viewpoints, Sea of Sand, jeep route, crater area, and Tengger highland scenery.',
    },
    sourceRefs: ['https://mountbromo.org/', 'https://www.bromo.co.id/'],
  },
  {
    id: 'jogja',
    name: 'Jogja',
    region: 'Yogyakarta',
    province: 'Special Region of Yogyakarta',
    image: '/images/destinations/jogja.jpg',
    copy: {
      id: 'Heritage candi, budaya kota, kuliner lokal, lanskap Merapi, dan rute yang cocok untuk private group.',
      cn: '寺庙文化遗产、城市文化、当地美食、Merapi 景观，以及适合私人团的路线。',
      us: 'Temple heritage, city culture, local cuisine, Merapi scenery, and private group-friendly routes.',
    },
    sourceRefs: ['https://www.indonesia.travel/id/en/destination/java/yogyakarta/yogyakarta---prambanan/'],
  },
  {
    id: 'tumpak-sewu',
    name: 'Tumpak Sewu',
    region: 'Lumajang',
    province: 'East Java',
    image: '/images/destinations/tumpak-sewu.jpg',
    copy: {
      id: 'Panorama air terjun bertingkat, jalur alam Pronojiwo, trekking basah, dan view canyon dramatis.',
      cn: '层叠瀑布全景、Pronojiwo 自然步道、湿滑徒步和壮观峡谷景色。',
      us: 'Tiered waterfall panorama, Pronojiwo nature trails, wet trekking, and dramatic canyon views.',
    },
    sourceRefs: ['https://www.airterjuntumpaksewu.com/'],
  },
  {
    id: 'medan',
    name: 'Medan',
    region: 'North Sumatra',
    province: 'North Sumatra',
    image: '/images/destinations/medan.jpg',
    copy: {
      id: 'Akses Lake Toba, kuliner Medan, budaya Batak, dan rute Sumatra dengan tempo santai.',
      cn: 'Lake Toba 路线、Medan 美食、Batak 文化和节奏轻松的苏门答腊行程。',
      us: 'Lake Toba access, Medan culinary stops, Batak culture, and relaxed Sumatra escape routes.',
    },
    sourceRefs: ['https://www.roughguides.com/indonesia/sumatra/lake-toba/'],
  },
];

export function getDestinationById(destinationId) {
  return destinations.find((destination) => destination.id === destinationId);
}

export function getDestinationByName(destinationName) {
  return destinations.find((destination) => destination.name === destinationName);
}
