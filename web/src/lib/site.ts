/** Публичный адрес витрины: canonical-ссылки, sitemap, robots. Вшивается при сборке. */
export const SITE_URL = (process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3001").replace(/\/$/, "");

export const carPath = (make: string, model: string, category?: string) =>
  `/cars/${make}/${model}${category ? `/${category}` : ""}`;

/** «2015–н.в.» / «1984–2012» */
export function formatYears(from: number | null, to: number | null): string {
  if (!from) return "";
  return `${from}–${to ?? "н.в."}`;
}

export function xmlResponse(content: string) {
  return new Response(`<?xml version="1.0" encoding="UTF-8"?>${content}`, {
    headers: { "Content-Type": "application/xml; charset=utf-8", "Cache-Control": "public, max-age=3600" },
  });
}
