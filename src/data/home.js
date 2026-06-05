import { Compass, MapPin, ShieldCheck, Star, Users } from 'lucide-react';

export const homeTrustItems = [
  { title: { id: 'Ulasan Google', cn: 'Google 评价', us: 'Google Reviews' }, value: '5.0', icon: Star },
  { title: { id: 'NPS Customer', cn: '客户 NPS', us: 'NPS Customer' }, value: '9.9/10', icon: ShieldCheck },
  { title: { id: 'Planner Tersertifikasi', cn: '认证行程规划', us: 'Certified Planner' }, value: { id: 'Standar Resmi', cn: '官方标准', us: 'Official Standard' }, icon: Compass },
  { title: { id: 'Tim Lokal', cn: '当地团队', us: 'Local Team' }, value: { id: 'Berbasis di Malang', cn: 'Malang 本地', us: 'Malang Based' }, icon: MapPin },
];

export const whyChooseItems = [
  {
    title: { id: 'Itinerary Jelas', cn: '清晰行程', us: 'Clear Itinerary' },
    text: {
      id: 'Setiap rute menjelaskan waktu, titik jemput, fasilitas, exclude, dan catatan sebelum kamu booking.',
      cn: '每条路线在预订前说明时间、接送点、包含项目、不包含项目和重要备注。',
      us: 'Every route explains timing, pickup point, inclusions, exclusions, and notes before you book.',
    },
    icon: Compass,
  },
  {
    title: { id: 'Guide Lokal', cn: '当地向导', us: 'Local Guide' },
    text: {
      id: 'Tim lokal memahami medan Bromo, cuaca, akses destinasi, dan kenyamanan traveler.',
      cn: '当地团队熟悉 Bromo 地形、天气、目的地交通和旅客舒适度。',
      us: 'Local teams understand Bromo terrain, weather, destination access, and traveler comfort.',
    },
    icon: MapPin,
  },
  {
    title: { id: 'Pickup Fleksibel', cn: '灵活接送', us: 'Flexible Pickup' },
    text: {
      id: 'Pilih hotel, bandara, stasiun, meeting point, atau request pickup custom.',
      cn: '可选择酒店、机场、火车站、集合点，或申请定制接送。',
      us: 'Choose hotel, airport, train station, meeting point, or request a custom pickup arrangement.',
    },
    icon: Users,
  },
];

export const homeReviews = [
  {
    name: 'Joe',
    origin: { id: 'UK', cn: '英国', us: 'UK' },
    text: {
      id: 'Trip Bromo lancar dari pickup sampai sunrise. Guidenya tepat waktu, membantu, dan mudah diajak komunikasi.',
      cn: 'Bromo 行程从接送到日出都很顺利。向导准时、细心，沟通也很方便。',
      us: 'The Bromo trip was smooth from pickup to sunrise. The guide was on time, helpful, and easy to communicate with.',
    },
  },
  {
    name: 'Sarah',
    origin: { id: 'Australia', cn: '澳大利亚', us: 'Australia' },
    text: {
      id: 'Itinerary jelas, transport nyaman, dan komunikasi cepat sebelum trip. Sangat membantu untuk tamu internasional.',
      cn: '行程清楚、交通舒适，出发前沟通很快。对国际旅客非常有帮助。',
      us: 'Clear itinerary, comfortable transport, and fast communication before the trip. Very useful for international guests.',
    },
  },
  {
    name: 'Ryo',
    origin: { id: 'Jepang', cn: '日本', us: 'Japan' },
    text: {
      id: 'Semuanya terasa rapi sejak chat pertama. Tim membantu kami memahami rute sebelum booking.',
      cn: '从第一次聊天开始，一切都安排得很清楚。团队在预订前帮我们了解路线。',
      us: 'Everything felt organized from the first chat. The team helped us understand the route before we booked.',
    },
  },
];
