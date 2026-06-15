import { useEffect } from 'react';
import { absoluteUrl, defaultSeo, siteName } from '../../utils/seo';

function setMetaAttribute(selector, attribute, value) {
  let element = document.head.querySelector(selector);

  if (!element) {
    element = document.createElement('meta');
    const nameMatch = selector.match(/meta\[name="([^"]+)"\]/);
    const propertyMatch = selector.match(/meta\[property="([^"]+)"\]/);

    if (nameMatch) {
      element.setAttribute('name', nameMatch[1]);
    }

    if (propertyMatch) {
      element.setAttribute('property', propertyMatch[1]);
    }

    document.head.appendChild(element);
  }

  element.setAttribute(attribute, value);
}

function setCanonical(url) {
  let canonical = document.head.querySelector('link[rel="canonical"]');

  if (!canonical) {
    canonical = document.createElement('link');
    canonical.setAttribute('rel', 'canonical');
    document.head.appendChild(canonical);
  }

  canonical.setAttribute('href', url);
}

function removeMeta(selector) {
  document.head.querySelector(selector)?.remove();
}

function setJsonLd(id, data) {
  document.head.querySelectorAll(`script[data-seo-jsonld="${id}"]`).forEach((item) => item.remove());

  if (!data) {
    return;
  }

  const script = document.createElement('script');
  script.type = 'application/ld+json';
  script.dataset.seoJsonld = id;
  script.textContent = JSON.stringify(data);
  document.head.appendChild(script);
}

const htmlLangByRegion = {
  id: 'id',
  us: 'en',
  en: 'en',
  cn: 'zh-CN',
  zh: 'zh-CN',
};

export function Seo({
  title,
  description,
  path = '/',
  image = defaultSeo.image,
  noindex = false,
  jsonLd = null,
  language = 'id',
  type = 'website',
  publishedTime,
  modifiedTime,
}) {
  useEffect(() => {
    const pageTitle = title || defaultSeo.title;
    const pageDescription = description || defaultSeo.description;
    const canonicalUrl = absoluteUrl(path);
    const imageUrl = absoluteUrl(image || defaultSeo.image);

    document.title = pageTitle;
    document.documentElement.lang = htmlLangByRegion[language] ?? 'id';

    setMetaAttribute('meta[name="description"]', 'content', pageDescription);
    setMetaAttribute('meta[name="robots"]', 'content', noindex ? 'noindex,nofollow' : 'index,follow');
    setMetaAttribute('meta[property="og:site_name"]', 'content', siteName);
    setMetaAttribute('meta[property="og:title"]', 'content', pageTitle);
    setMetaAttribute('meta[property="og:description"]', 'content', pageDescription);
    setMetaAttribute('meta[property="og:type"]', 'content', type);
    setMetaAttribute('meta[property="og:url"]', 'content', canonicalUrl);
    setMetaAttribute('meta[property="og:image"]', 'content', imageUrl);
    if (publishedTime) {
      setMetaAttribute('meta[property="article:published_time"]', 'content', publishedTime);
    } else {
      removeMeta('meta[property="article:published_time"]');
    }
    if (modifiedTime) {
      setMetaAttribute('meta[property="article:modified_time"]', 'content', modifiedTime);
    } else {
      removeMeta('meta[property="article:modified_time"]');
    }
    setMetaAttribute('meta[name="twitter:card"]', 'content', 'summary_large_image');
    setMetaAttribute('meta[name="twitter:title"]', 'content', pageTitle);
    setMetaAttribute('meta[name="twitter:description"]', 'content', pageDescription);
    setMetaAttribute('meta[name="twitter:image"]', 'content', imageUrl);
    setCanonical(canonicalUrl);
    setJsonLd('page', jsonLd);

    return () => {
      setJsonLd('page', null);
    };
  }, [description, image, jsonLd, language, modifiedTime, noindex, path, publishedTime, title, type]);

  return null;
}
