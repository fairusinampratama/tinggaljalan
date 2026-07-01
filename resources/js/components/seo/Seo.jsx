import { Head } from '@inertiajs/react';
import { useEffect } from 'react';
import { absoluteUrl, defaultSeo, siteName } from '../../utils/seo';

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
  canonical,
  image = defaultSeo.image,
  noindex = false,
  robots,
  jsonLd = null,
  json_ld = null,
  language = 'id',
  type = 'website',
  og_type,
  publishedTime,
  published_time,
  modifiedTime,
  modified_time,
}) {
  const pageTitle = title || defaultSeo.title;
  const pageDescription = description || defaultSeo.description;
  const canonicalUrl = canonical || absoluteUrl(path);
  const imageUrl = absoluteUrl(image || defaultSeo.image);
  const pageType = og_type || type;
  const published = published_time || publishedTime;
  const modified = modified_time || modifiedTime;
  const schema = json_ld || jsonLd;
  const htmlLang = htmlLangByRegion[language] ?? 'id';

  useEffect(() => {
    document.documentElement.lang = htmlLang;
  }, [htmlLang]);

  return (
    <Head>
      <title>{pageTitle}</title>
      <meta name="description" content={pageDescription} />
      <meta name="robots" content={robots || (noindex ? 'noindex,nofollow' : 'index,follow')} />
      <link rel="canonical" href={canonicalUrl} />
      <meta property="og:site_name" content={siteName} />
      <meta property="og:title" content={pageTitle} />
      <meta property="og:description" content={pageDescription} />
      <meta property="og:type" content={pageType} />
      <meta property="og:url" content={canonicalUrl} />
      <meta property="og:image" content={imageUrl} />
      {published ? <meta property="article:published_time" content={published} /> : null}
      {modified ? <meta property="article:modified_time" content={modified} /> : null}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={pageTitle} />
      <meta name="twitter:description" content={pageDescription} />
      <meta name="twitter:image" content={imageUrl} />
      {schema ? <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }} /> : null}
    </Head>
  );
}
