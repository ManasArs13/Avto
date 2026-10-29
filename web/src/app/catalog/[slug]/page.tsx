import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { Pagination, parsePage } from "@/components/pagination";
import { ProductRow } from "@/components/product-card";
import { getCarModification, getCategories, getCategoryProducts } from "@/lib/data";

async function findCategory(slug: string) {
  const categories = await getCategories();
  return categories.flatMap((c) => [c, ...(c.children ?? [])]).find((c) => c.slug === slug);
}

export async function generateMetadata(props: PageProps<"/catalog/[slug]">): Promise<Metadata> {
  const { slug } = await props.params;
  const category = await findCategory(slug);
  if (!category) return { title: "Категория не найдена" };
  return {
    title: `${category.name} ВАЗ (LADA)`,
    description: `${category.name} для автомобилей LADA: оригинальные запчасти ВАЗ и аналоги, цены и наличие.`,
    // Фильтр по машине и номер страницы — варианты одной и той же страницы.
    alternates: { canonical: `/catalog/${slug}` },
  };
}

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
  const pageNum = parsePage(page);

  const [category, result, modification] = await Promise.all([
    findCategory(slug),
    getCategoryProducts(slug, carId, pageNum),
    carId ? getCarModification(carId) : null,
  ]);
  if (!result) notFound();

  const pageLink = (p: number) =>
    `/catalog/${slug}?${new URLSearchParams({ ...(carId ? { car: String(carId) } : {}), page: String(p) })}`;

  return (
    <div className="space-y-4">
      <Breadcrumbs items={[{ name: category?.name ?? "Каталог" }]} />
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

      <Pagination page={pageNum} lastPage={result.meta.last_page} href={pageLink} />
    </div>
  );
}
