import type { Metadata } from "next";
import { Suspense } from "react";
import { ProductRow } from "@/components/product-card";
import { searchProducts } from "@/lib/data";

export const metadata: Metadata = { title: "Поиск" };

export default function SearchPage(props: PageProps<"/search">) {
  return (
    <Suspense fallback={<ResultsSkeleton />}>
      <Results searchParams={props.searchParams} />
    </Suspense>
  );
}

async function Results({ searchParams }: Pick<PageProps<"/search">, "searchParams">) {
  const { q } = await searchParams;
  const query = typeof q === "string" ? q.trim() : "";

  if (query.length < 2) {
    return <p className="text-muted">Введите хотя бы 2 символа.</p>;
  }

  const products = await searchProducts(query);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">
        Результаты по запросу <span className="article">«{query}»</span>
      </h1>
      {products.length > 1 && products.some((p) => p.article === products[0].article) && (
        <p className="text-sm text-muted">Этот артикул есть у нескольких производителей — выберите нужного.</p>
      )}
      {products.length === 0 ? (
        <div className="card p-6 text-muted">
          Ничего не нашли. Проверьте артикул или попробуйте поиск по названию детали.
        </div>
      ) : (
        <div className="space-y-2">
          {products.map((p) => (
            <ProductRow key={p.id} product={p} />
          ))}
        </div>
      )}
    </div>
  );
}

function ResultsSkeleton() {
  return (
    <div className="space-y-2">
      <div className="mb-4 h-8 w-80 animate-pulse rounded bg-border" />
      {Array.from({ length: 4 }, (_, i) => (
        <div key={i} className="card h-16 animate-pulse" />
      ))}
    </div>
  );
}
