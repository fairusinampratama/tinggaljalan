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
      <meta head-key="description" name="description" content={pageDescription} />
      <meta head-key="robots" name="robots" content={robots || (noindex ? 'noindex,nofollow' : 'index,follow')} />
      <link head-key="canonical" rel="canonical" href={canonicalUrl} />
      <meta head-key="og:site_name" property="og:site_name" content={siteName} />
      <meta head-key="og:title" property="og:title" content={pageTitle} />
      <meta head-key="og:description" property="og:description" content={pageDescription} />
      <meta head-key="og:type" property="og:type" content={pageType} />
      <meta head-key="og:url" property="og:url" content={canonicalUrl} />
      <meta head-key="og:image" property="og:image" content={imageUrl} />
      {published ? <meta head-key="article:published_time" property="article:published_time" content={published} /> : null}
      {modified ? <meta head-key="article:modified_time" property="article:modified_time" content={modified} /> : null}
      <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
      <meta head-key="twitter:title" name="twitter:title" content={pageTitle} />
      <meta head-key="twitter:description" name="twitter:description" content={pageDescription} />
      <meta head-key="twitter:image" name="twitter:image" content={imageUrl} />
      {schema ? <script head-key="json-ld" type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }} /> : null}
    </Head>
  );
}
