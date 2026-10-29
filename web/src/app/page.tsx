import Link from "next/link";
import { connection } from "next/server";
import { Suspense } from "react";
import { CategoryLinks } from "@/components/category-links";
import { SearchForm } from "@/components/search-form";
import { VehicleSelector } from "@/components/vehicle-selector";
import { getCarMakes, getCarModels, getCategories } from "@/lib/data";
import { carPath } from "@/lib/site";

export default function Home() {
  return (
    <div className="space-y-10">
      <section className="card px-5 py-8 sm:px-10 sm:py-12">
        <h1 className="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
          Оригинальные запчасти <span className="text-accent">ВАЗ</span> — по номеру и по машине
        </h1>
        <p className="mt-3 max-w-2xl text-muted">
          LADA Vesta, Granta, Largus, Niva и классика. Введите каталожный номер в любом формате или выберите автомобиль.
        </p>
        <div className="mt-6 max-w-2xl">
          <SearchForm large />
        </div>
        <div className="mt-3 flex flex-wrap gap-2 text-sm text-muted">
          Например:
          {["2108-3501080", "21080101200508", "W 914/2", "ремень ГРМ"].map((q) => (
            <a key={q} href={`/search?q=${encodeURIComponent(q)}`} className="article text-foreground hover:text-accent">
              {q}
            </a>
          ))}
        </div>
      </section>

      <Suspense fallback={<CatalogSkeleton />}>
        <CatalogSections />
      </Suspense>
    </div>
  );
}

async function CatalogSections() {
  // Данные из API берём при запросе, а не при сборке: образ собирается без работающего бэкенда.
  // Ответы всё равно кэшируются (use cache в lib/data.ts).
  await connection();
  const [categories, makes] = await Promise.all([getCategories(), getCarMakes()]);
  // Магазин одной марки (LADA): модели загружаем сразу, без выбора марки.
  const models = makes.length === 1 ? await getCarModels(makes[0].id) : [];

  return (
    <>
      <section className="card p-5">
        <h2 className="mb-3 text-lg font-bold">Подбор по автомобилю</h2>
        <VehicleSelector makes={makes} initialModels={models} />
        {models.length > 0 && (
          <div className="mt-4 flex flex-wrap gap-2 text-sm">
            {models.map((m) => (
              <Link
                key={m.id}
                href={carPath(makes[0].slug, m.slug)}
                className="rounded-lg border border-border px-3 py-1.5 hover:border-accent hover:text-accent"
              >
                {makes[0].name} {m.name}
              </Link>
            ))}
          </div>
        )}
      </section>

      <section>
        <h2 className="mb-4 text-lg font-bold">Каталог</h2>
        <CategoryLinks categories={categories} />
      </section>
    </>
  );
}

function CatalogSkeleton() {
  return (
    <div className="space-y-10">
      <div className="card h-28 animate-pulse" />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {Array.from({ length: 4 }, (_, i) => (
          <div key={i} className="card h-32 animate-pulse" />
        ))}
      </div>
    </div>
  );
}
