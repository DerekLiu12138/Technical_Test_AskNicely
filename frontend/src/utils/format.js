// Cache NumberFormat to avoid recreating it repeatedly
const formatters = new Map();

function getFormatter(locale = undefined, currency = 'NZD') {
  const key = `${locale}|${currency}`;
  if (!formatters.has(key)) {
    formatters.set(key, new Intl.NumberFormat(locale, {
      style: 'currency',
      currency,
      maximumFractionDigits: 0
    }));
  }
  return formatters.get(key);
}

// Format value as currency; return original value if not finite
export const asCurrency = (v, currency = 'NZD') => {
  const n = Number(v);
  return Number.isFinite(n)
    ? getFormatter(undefined, currency).format(n)
    : v;
};
