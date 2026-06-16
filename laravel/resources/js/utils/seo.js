import { getLocalized, getRegionConfig, localizeDuration } from './localization';

export const siteBaseUrl = 'https://tinggaljalan.com';
export const defaultOgImage = '/images/hero-bromo.jpg';
export const siteName = 'Tinggal Jalan';

export const defaultSeo = {
  title: 'Tinggal Jalan | Indonesia Tours & Private Trips',
  description:
    'Plan private Indonesia tours with Tinggal Jalan. Compare Bromo, Tumpak Sewu, Jogja, and Medan routes with clear itineraries, flexible pickup, and WhatsApp support.',
  path: '/',
  image: defaultOgImage,
};

export function absoluteUrl(path = '/') {
  if (/^https?:\/\//i.test(path)) {
    return path;
  }

  return `${siteBaseUrl}${path.startsWith('/') ? path : `/${path}`}`;
}

export function getRouteSeo(route, language = 'us') {
  const region = getRegionConfig(language);
  const title = getLocalized(route.title, language);
  const destination = getLocalized(route.destinationName, language);
  const duration = localizeDuration(route.duration, language);
  const description = getLocalized(route.intro ?? route.bestFor ?? route.why, language);

  return {
    title: `${title} | ${destination} Tour | Tinggal Jalan`,
    description: `${description} Duration: ${duration}. Private trip planning, flexible pickup, and confirmation via WhatsApp.`,
    path: `/routes/${route.id}`,
    image: route.image,
    currency: region.currency,
  };
}

export function buildRouteJsonLd(route, language = 'us') {
  const seo = getRouteSeo(route, language);
  const price = seo.currency === 'USD' ? route.basePriceUsd : route.basePriceIdr ?? route.basePrice;

  return {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: getLocalized(route.title, language),
    description: seo.description,
    image: absoluteUrl(route.image),
    brand: {
      '@type': 'Brand',
      name: siteName,
    },
    category: 'Tour',
    areaServed: getLocalized(route.destinationName, language),
    offers: {
      '@type': 'Offer',
      priceCurrency: seo.currency,
      price,
      availability: 'https://schema.org/InStock',
      url: absoluteUrl(`/routes/${route.id}`),
    },
    aggregateRating: {
      '@type': 'AggregateRating',
      ratingValue: route.rating ?? 5,
      reviewCount: route.reviewCount ?? 1,
      bestRating: 5,
      worstRating: 1,
    },
  };
}

export function getNewsSeo(article, language = 'us') {
  const title = getLocalized(article.seo?.title ?? article.title, language);
  const description = getLocalized(article.seo?.description ?? article.excerpt, language);

  return {
    title,
    description,
    path: `/news/${article.slug}`,
    image: article.coverImage,
    type: 'article',
    publishedTime: article.publishedDate,
    modifiedTime: article.updatedDate ?? article.publishedDate,
  };
}

export function buildNewsArticleJsonLd(article, language = 'us') {
  const seo = getNewsSeo(article, language);

  return {
    '@context': 'https://schema.org',
    '@type': article.category === 'kabar' ? 'NewsArticle' : 'BlogPosting',
    headline: getLocalized(article.title, language),
    description: seo.description,
    image: absoluteUrl(article.coverImage),
    datePublished: article.publishedDate,
    dateModified: article.updatedDate ?? article.publishedDate,
    author: {
      '@type': 'Organization',
      name: siteName,
    },
    publisher: {
      '@type': 'Organization',
      name: siteName,
      logo: {
        '@type': 'ImageObject',
        url: absoluteUrl('/favicon.svg'),
      },
    },
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': absoluteUrl(`/news/${article.slug}`),
    },
  };
}
