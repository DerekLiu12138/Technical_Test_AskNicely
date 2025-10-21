export const asCurrency = (v) => {
    const n = Number(v)
    return Number.isFinite(n)
      ? n.toLocaleString(undefined, { style: 'currency', currency: 'NZD', maximumFractionDigits: 0 })
      : v
  }
  