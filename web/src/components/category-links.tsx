"use client";

import type { Category } from "@avto/shared";
import Link from "next/link";
import { useSelectedCar } from "@/lib/car";

/** Ссылки на категории; если выбран автомобиль — сразу с фильтром по нему. */
export function CategoryLinks({ categories }: { categories: Category[] }) {
  const car = useSelectedCar();
  const suffix = car ? `?car=${car.id}` : "";

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {categories.map((parent) => (
        <div key={parent.id} className="card p-4">
          <h3 className="mb-2 font-bold">{parent.name}</h3>
          <ul className="space-y-1 text-sm">
            {parent.children?.map((c) => (
              <li key={c.id}>
                <Link href={`/catalog/${c.slug}${suffix}`} className="text-muted hover:text-accent">
                  {c.name}
                </Link>
              </li>
            ))}
          </ul>
        </div>
      ))}
    </div>
  );
}
