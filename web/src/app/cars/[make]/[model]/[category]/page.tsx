import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { Pagination, parsePage } from "@/components/pagination";
import { ProductRow } from "@/components/product-card";
import { getCarModelPage, getCategoryProducts } from "@/lib/data";
import { carPath } from "@/lib/site";

type Props = PageProps<"/cars/[make]/[model]/[category]">;

/** Модель + категория; null, если такой пары нет или для модели в категории ничего нет. */
async function load(params: Props["params"]) {
  const slugs = await params;
  const data = await getCarModelPage(slugs.make, slugs.model);
  const category = data?.categories.find((c) => c.slug === slugs.category);
  return data && category ? { ...data, category, slugs } : null;
}

export async function generateMetadata(props: Props): Promise<Metadata> {
  const data = await load(props.params);
  if (!data) return { title: "Страница не найдена" };
  const name = `${data.make.name} ${data.model.name}`;
  return {
    title: `${data.category.name} ${name}`,
    description: `${data.category.name} для ${name}: оригинальные запчасти ВАЗ и проверенные аналоги, ${data.category.products_count} шт. в наличии.`,
    alternates: { canonical: carPath(data.slugs.make, data.slugs.model, data.slugs.category) },
  };
}

export default function CarCategoryPage(props: Props) {
  return (
    <Suspense fallback={<div className="card h-64 animate-pulse" />}>
      <CategoryProducts params={props.params} searchParams={props.searchParams} />
    </Suspense>
  );
}

async function CategoryProducts({ params, searchParams }: Pick<Props, "params" | "searchParams">) {
  const data = await load(params);
  if (!data) notFound();

  const page = parsePage((await searchParams).page);
  const result = await getCategoryProducts(data.category.slug, undefined, page, data.model.id);
  if (!result) notFound();

  const name = `${data.make.name} ${data.model.name}`;
  const { make, model, category } = data.slugs;

  return (
    <div className="space-y-4">
      <Breadcrumbs
        items={[
          { name: "Модели", href: "/cars" },
          { name, href: carPath(make, model) },
          { name: data.category.name },
        ]}
      />
      <h1 className="text-2xl font-bold">
        {data.category.name} {name}
      </h1>

      <div className="space-y-2">
        {result.data.map((p) => (
          <ProductRow key={p.id} product={p} />
        ))}
      </div>

      <Pagination
        page={page}
        lastPage={result.meta.last_page}
        href={(p) => `${carPath(make, model, category)}${p > 1 ? `?page=${p}` : ""}`}
      />
    </div>
  );
}
