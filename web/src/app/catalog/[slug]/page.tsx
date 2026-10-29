import Link from "next/link";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { ProductRow } from "@/components/product-card";
import { getCarModification, getCategories, getCategoryProducts } from "@/lib/data";

export default function CatalogPage(props: PageProps<"/catalog/[slug]">) {
  return (
    <Suspense fallback={<div className="card h-64 animate-pulse" />}>
      <CategoryProducts params={props.params} searchParams={props.searchParams} />
    </Suspense>
  );
}

async function CategoryProducts({ params, searchParams }: PageProps<"/catalog/[slug]">) {
  const { slug } = await params;
  const { car, page } = await searchParams;
  const carId = typeof car === "string" && /^\d+$/.test(car) ? Number(car) : undefined;
  const pageNum = typeof page === "string" && /^\d+$/.test(page) ? Number(page) : 1;

  const [categories, result, modification] = await Promise.all([
    getCategories(),
    getCategoryProducts(slug, carId, pageNum),
    carId ? getCarModification(carId) : null,
  ]);
  if (!result) notFound();

  const category = categories.flatMap((c) => [c, ...(c.children ?? [])]).find((c) => c.slug === slug);
  const pageLink = (p: number) => `/catalog/${slug}?${new URLSearchParams({ ...(carId ? { car: String(carId) } : {}), page: String(p) })}`;

  return (
    <div className="space-y-4">
      <nav className="text-sm text-muted">
        <Link href="/" className="hover:text-accent">
          Главная
        </Link>{" "}
        / {category?.name}
      </nav>
      <h1 className="text-2xl font-bold">{category?.name ?? "Каталог"}</h1>

      {modification && (
        <div className="flex flex-wrap items-center gap-3 text-sm">
          <span className="rounded-lg bg-accent/10 px-3 py-1.5 font-medium text-accent">
            Подходит для {modification.title}
          </span>
          <Link href={`/catalog/${slug}`} className="text-muted hover:text-accent">
            Показать для всех авто
          </Link>
        </div>
      )}

      {result.data.length === 0 ? (
        <div className="card p-6 text-muted">В этой категории пока нет товаров в наличии.</div>
      ) : (
        <div className="space-y-2">
          {result.data.map((p) => (
            <ProductRow key={p.id} product={p} />
          ))}
        </div>
      )}

      {result.meta.last_page > 1 && (
        <div className="flex justify-center gap-2">
          {pageNum > 1 && (
            <Link href={pageLink(pageNum - 1)} className="btn-ghost">
              ← Назад
            </Link>
          )}
          <span className="px-3 py-2 text-sm text-muted">
            {pageNum} из {result.meta.last_page}
          </span>
          {pageNum < result.meta.last_page && (
            <Link href={pageLink(pageNum + 1)} className="btn-ghost">
              Вперёд →
            </Link>
          )}
        </div>
      )}
    </div>
  );
}
