import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { getCarModelPage } from "@/lib/data";
import { carPath, formatYears } from "@/lib/site";

type Props = PageProps<"/cars/[make]/[model]">;

export async function generateMetadata(props: Props): Promise<Metadata> {
  const { make, model } = await props.params;
  const data = await getCarModelPage(make, model);
  if (!data) return { title: "Модель не найдена" };
  const name = `${data.make.name} ${data.model.name}`;
  const topics = data.categories
    .slice(0, 5)
    .map((c) => c.name.toLowerCase())
    .join(", ");
  return {
    title: `Запчасти ${name} — оригинал ВАЗ`,
    description: `Оригинальные запчасти для ${name}: ${topics}. Подбор по двигателю, цены и наличие.`,
    alternates: { canonical: carPath(make, model) },
  };
}

export default function CarModelPage(props: Props) {
  return (
    <Suspense fallback={<div className="card h-64 animate-pulse" />}>
      <ModelDetails params={props.params} />
    </Suspense>
  );
}

async function ModelDetails({ params }: Pick<Props, "params">) {
  const { make, model } = await params;
  const data = await getCarModelPage(make, model);
  if (!data) notFound();

  const name = `${data.make.name} ${data.model.name}`;
  const years = formatYears(data.model.year_from, data.model.year_to);
  // Категории приходят отсортированными; группируем по разделу (Техобслуживание, Двигатель…).
  const groups = Map.groupBy(data.categories, (c) => c.parent.name);

  return (
    <div className="space-y-6">
      <Breadcrumbs items={[{ name: "Модели", href: "/cars" }, { name }]} />

      <section className="card p-5 sm:p-8">
        <h1 className="text-2xl font-black tracking-tight sm:text-3xl">Запчасти {name}</h1>
        {years && <p className="mt-1 text-muted">Годы выпуска: {years}</p>}
        {data.modifications.length > 0 && (
          <div className="mt-4 flex flex-wrap gap-2 text-sm">
            {data.modifications.map((m) => (
              <span key={m.id} className="rounded-lg border border-border px-3 py-1.5">
                {m.name}
                {m.engine_code && <span className="text-muted"> · {m.engine_code}</span>}
              </span>
            ))}
          </div>
        )}
      </section>

      {data.categories.length === 0 ? (
        <div className="card p-6 text-muted">Для этой модели пока нет запчастей в наличии.</div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {[...groups].map(([group, categories]) => (
            <section key={group} className="card p-4">
              <h2 className="mb-2 font-bold">{group}</h2>
              <ul className="space-y-1 text-sm">
                {categories.map((c) => (
                  <li key={c.id} className="flex justify-between gap-3">
                    <Link href={carPath(make, model, c.slug)} className="text-muted hover:text-accent">
                      {c.name}
                    </Link>
                    <span className="tabular-nums text-muted">{c.products_count}</span>
                  </li>
                ))}
              </ul>
            </section>
          ))}
        </div>
      )}
    </div>
  );
}
