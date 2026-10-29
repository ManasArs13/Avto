import Link from "next/link";
import { SITE_URL } from "@/lib/site";

export interface Crumb {
  name: string;
  href?: string;
}

/** Хлебные крошки + разметка BreadcrumbList для поисковиков. */
export function Breadcrumbs({ items }: { items: Crumb[] }) {
  const all: Crumb[] = [{ name: "Главная", href: "/" }, ...items];
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: all.map((c, i) => ({
      "@type": "ListItem",
      position: i + 1,
      name: c.name,
      ...(c.href ? { item: SITE_URL + c.href } : {}),
    })),
  };

  return (
    <nav className="text-sm text-muted">
      {all.map((c, i) => (
        <span key={i}>
          {i > 0 && " / "}
          {c.href && i < all.length - 1 ? (
            <Link href={c.href} className="hover:text-accent">
              {c.name}
            </Link>
          ) : (
            c.name
          )}
        </span>
      ))}
      <JsonLd data={jsonLd} />
    </nav>
  );
}

export function JsonLd({ data }: { data: object }) {
  // "<" экранируем, чтобы строка из данных не закрыла тег script.
  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(data).replace(/</g, "\\u003c") }}
    />
  );
}
