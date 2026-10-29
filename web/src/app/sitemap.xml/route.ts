import { connection } from "next/server";
import { getSitemapData } from "@/lib/data";
import { SITE_URL, xmlResponse } from "@/lib/site";

/**
 * Индекс sitemap: страницы каталога в одном файле, товары — в нескольких
 * (до 40 000 ссылок в каждом, лимит поисковиков — 50 000).
 */
export async function GET() {
  await connection();
  const { product_pages } = await getSitemapData();

  const files = ["pages", ...Array.from({ length: product_pages }, (_, i) => `products-${i + 1}`)];
  const body = files.map((f) => `<sitemap><loc>${SITE_URL}/sitemaps/${f}.xml</loc></sitemap>`).join("");

  return xmlResponse(`<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${body}</sitemapindex>`);
}
