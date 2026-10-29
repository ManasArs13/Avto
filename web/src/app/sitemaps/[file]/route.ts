import { connection } from "next/server";
import { getSitemapData, getSitemapProducts } from "@/lib/data";
import { carPath, SITE_URL, xmlResponse } from "@/lib/site";

/** /sitemaps/pages.xml и /sitemaps/products-N.xml — см. индекс в /sitemap.xml. */
export async function GET(_request: Request, ctx: RouteContext<"/sitemaps/[file]">) {
  const { file } = await ctx.params;
  await connection();

  if (file === "pages.xml") {
    const data = await getSitemapData();
    const paths = [
      "/",
      "/cars",
      ...data.categories.map((slug) => `/catalog/${slug}`),
      ...data.models.flatMap((m) => [carPath(m.make, m.model), ...m.categories.map((c) => carPath(m.make, m.model, c))]),
    ];
    return urlset(paths.map((path) => ({ path })));
  }

  const page = /^products-(\d+)\.xml$/.exec(file)?.[1];
  if (!page) return new Response("Not found", { status: 404 });

  const products = await getSitemapProducts(Number(page));
  return urlset(products.map(([id, date]) => ({ path: `/product/${id}`, lastmod: date })));
}

function urlset(urls: { path: string; lastmod?: string }[]) {
  const body = urls
    .map((u) => `<url><loc>${SITE_URL}${u.path}</loc>${u.lastmod ? `<lastmod>${u.lastmod}</lastmod>` : ""}</url>`)
    .join("");
  return xmlResponse(`<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${body}</urlset>`);
}
