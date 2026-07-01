export const defaultRegion = 'us';

export const regions = {
  id: {
    id: 'id',
    label: 'ID',
    region: 'Indonesia',
    locale: 'id-ID',
    currency: 'IDR',
    travelerType: 'local',
    paymentGateway: 'Midtrans',
  },
  cn: {
    id: 'cn',
    label: '中文',
    region: 'Chinese',
    locale: 'zh-CN',
    currency: 'USD',
    travelerType: 'international',
    paymentGateway: 'Secure payment link after confirmation',
  },
  us: {
    id: 'us',
    label: 'EN',
    region: 'English',
    locale: 'en-US',
    currency: 'USD',
    travelerType: 'international',
    paymentGateway: 'Secure payment link after confirmation',
  },
};

export function normalizeRegion(region) {
  if (region === 'en') {
    return 'us';
  }

  if (region === 'zh') {
    return 'cn';
  }

  return regions[region] ? region : defaultRegion;
}

export function getRegionConfig(region) {
  return regions[normalizeRegion(region)];
}

export function getLocalized(value, region = defaultRegion) {
  const normalizedRegion = normalizeRegion(region);

  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return value[normalizedRegion] ?? value.us ?? value.en ?? value.id ?? value.cn ?? '';
  }

  return value ?? '';
}

export function localizeList(items = [], region = defaultRegion) {
  const localizedItems = getLocalized(items, region);

  if (Array.isArray(localizedItems)) {
    return localizedItems.map((item) => getLocalized(item, region));
  }

  if (localizedItems) {
    return [getLocalized(localizedItems, region)];
  }

  return [];
}

const durationLabels = {
  'Half Day': {
    id: 'Setengah hari',
    cn: '半天',
    us: 'Half day',
  },
  '1 Day': {
    id: '1 hari',
    cn: '1天',
    us: '1 day',
  },
  '2D1N': {
    id: '2H1M',
    cn: '2天1晚',
    us: '2D1N',
  },
  '3D2N': {
    id: '3H2M',
    cn: '3天2晚',
    us: '3D2N',
  },
};

export function localizeDuration(duration, region = defaultRegion) {
  return getLocalized(durationLabels[duration] ?? duration, region);
}
