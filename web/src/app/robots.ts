import type { MetadataRoute } from "next";
import { SITE_URL } from "@/lib/site";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
      // Служебные страницы и поиск (бесконечное число URL) не индексируем.
      disallow: ["/cart", "/checkout", "/order/", "/account", "/search"],
    },
    sitemap: `${SITE_URL}/sitemap.xml`,
  };
}
