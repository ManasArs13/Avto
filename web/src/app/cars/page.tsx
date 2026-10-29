import type { Metadata } from "next";
import Link from "next/link";
import { connection } from "next/server";
import { Suspense } from "react";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { getAllCarModels } from "@/lib/data";
import { carPath, formatYears } from "@/lib/site";

export const metadata: Metadata = {
  title: "Запчасти по моделям LADA",
  description:
    "Оригинальные запчасти для всех моделей LADA: Vesta, Granta, Largus, Niva, Priora, Kalina и классика ВАЗ.",
  alternates: { canonical: "/cars" },
};

export default function CarsPage() {
  return (
    <div className="space-y-6">
      <Breadcrumbs items={[{ name: "Модели" }]} />
      <h1 className="text-2xl font-bold">Запчасти по моделям</h1>
      <Suspense fallback={<div className="card h-64 animate-pulse" />}>
        <ModelList />
      </Suspense>
    </div>
  );
}

async function ModelList() {
  // Данные — при запросе, а не при сборке (образ собирается без бэкенда).
  await connection();
  const makes = await getAllCarModels();

  return makes.map(({ make, models }) => (
    <section key={make.id} className="space-y-3">
      {makes.length > 1 && <h2 className="text-lg font-bold">{make.name}</h2>}
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {models.map((model) => (
          <Link
            key={model.id}
            href={carPath(make.slug, model.slug)}
            className="card flex items-baseline justify-between gap-3 px-4 py-3 transition-colors hover:border-accent"
          >
            <span className="font-semibold">
              {make.name} {model.name}
            </span>
            <span className="text-sm text-muted">{formatYears(model.year_from, model.year_to)}</span>
          </Link>
        ))}
      </div>
    </section>
  ));
}
