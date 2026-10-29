import { CategoryLinks } from "@/components/category-links";
import { SearchForm } from "@/components/search-form";
import { VehicleSelector } from "@/components/vehicle-selector";
import { getCarMakes, getCategories } from "@/lib/data";

export default async function Home() {
  const [categories, makes] = await Promise.all([getCategories(), getCarMakes()]);

  return (
    <div className="space-y-10">
      <section className="card px-5 py-8 sm:px-10 sm:py-12">
        <h1 className="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
          Запчасти по артикулу — <span className="text-accent">оригинал и аналоги</span>
        </h1>
        <p className="mt-3 max-w-2xl text-muted">
          Введите номер детали в любом формате: с пробелами, дефисами, кириллицей вместо латиницы — найдём.
        </p>
        <div className="mt-6 max-w-2xl">
          <SearchForm large />
        </div>
        <div className="mt-3 flex flex-wrap gap-2 text-sm text-muted">
          Например:
          {["04465-33450", "90915YZZD4", "W 68/3", "фильтр салонный"].map((q) => (
            <a key={q} href={`/search?q=${encodeURIComponent(q)}`} className="article text-foreground hover:text-accent">
              {q}
            </a>
          ))}
        </div>
      </section>

      <section className="card p-5">
        <h2 className="mb-3 text-lg font-bold">Подбор по автомобилю</h2>
        <VehicleSelector makes={makes} />
      </section>

      <section>
        <h2 className="mb-4 text-lg font-bold">Каталог</h2>
        <CategoryLinks categories={categories} />
      </section>
    </div>
  );
}
