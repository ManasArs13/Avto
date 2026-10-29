import { ApiError } from "@avto/shared";
import { cacheLife, cacheTag } from "next/cache";
import { api } from "./api";

// Серверные запросы к API с кэшированием (Cache Components).
// Справочники кэшируем на часы, цены и наличие — на минуты.

export async function getCategories() {
  "use cache";
  cacheLife("hours");
  cacheTag("categories");
  return api.catalog.categories();
}

export async function getCarMakes() {
  "use cache";
  cacheLife("hours");
  return api.vehicles.makes();
}

export async function getCarModification(id: number) {
  "use cache";
  cacheLife("hours");
  return orNull(api.vehicles.modification(id));
}

export async function searchProducts(q: string) {
  "use cache";
  cacheLife("minutes");
  return api.catalog.search(q);
}

export async function getProduct(id: number) {
  "use cache";
  cacheLife("minutes");
  cacheTag(`product-${id}`);
  return orNull(api.catalog.product(id));
}

export async function getCategoryProducts(slug: string, modification?: number, page?: number) {
  "use cache";
  cacheLife("minutes");
  return orNull(api.catalog.categoryProducts(slug, { modification, page }));
}

/** 404 от API превращаем в null, чтобы страница показала notFound(). */
async function orNull<T>(promise: Promise<T>): Promise<T | null> {
  try {
    return await promise;
  } catch (e) {
    if (e instanceof ApiError && e.status === 404) return null;
    throw e;
  }
}
