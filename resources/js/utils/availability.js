export function availabilityForDate(rules = [], date, fallbackByDate = {}) {
  if (!date) {
    return { status: 'available' };
  }

  const matches = rules.filter((rule) => {
    if (!rule?.startDate || rule.startDate > date) {
      return false;
    }

    return rule.openEnded || (rule.endDate ?? rule.startDate) >= date;
  });

  return matches.find((rule) => rule.scope === 'package')
    ?? matches.find((rule) => rule.scope === 'destination')
    ?? fallbackByDate[date]
    ?? { status: 'available' };
}

export function todayIsoDate() {
  const date = new Date();
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}
