export const languages = [
  { id: 'id', label: 'ID', region: 'Indonesia' },
  { id: 'cn', label: '\u4e2d\u6587', region: 'Chinese' },
  { id: 'us', label: 'EN', region: 'English' },
];

export const navTargets = ['#home', '#destination', '#articles', '#booking', '#contact'];

export async function loadCopy(language) {
  const normalized = language === 'en' ? 'us' : language === 'zh' ? 'cn' : language;

  switch (normalized) {
    case 'id':
      return (await import('./translations/id.js')).default;
    case 'cn':
      return (await import('./translations/cn.js')).default;
    case 'us':
    default:
      return (await import('./translations/us.js')).default;
  }
}
