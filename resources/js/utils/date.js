const localeByLanguage = {
  id: 'id-ID',
  en: 'en-US',
  zh: 'zh-CN',
  us: 'en-US',
  cn: 'zh-CN',
};

export function formatTravelDate(date, language = 'en') {
  if (!date) {
    return '-';
  }

  const parsedDate = new Date(`${date}T00:00:00`);

  if (Number.isNaN(parsedDate.getTime())) {
    return date;
  }

  return new Intl.DateTimeFormat(localeByLanguage[language] ?? 'en-US', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(parsedDate);
}
