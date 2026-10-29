const rub = new Intl.NumberFormat("ru-RU", { style: "currency", currency: "RUB", maximumFractionDigits: 0 });

export function formatPrice(value: number | null | undefined): string {
  return value == null ? "—" : rub.format(value);
}

/** 0 → «сегодня», 1 → «1 день», 5 → «5 дней». */
export function formatDelivery(days: number | null | undefined): string {
  if (days == null) return "—";
  if (days === 0) return "сегодня";
  return `${days} ${plural(days, ["день", "дня", "дней"])}`;
}

export function plural(n: number, forms: [string, string, string]): string {
  const mod10 = n % 10;
  const mod100 = n % 100;
  if (mod10 === 1 && mod100 !== 11) return forms[0];
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return forms[1];
  return forms[2];
}
