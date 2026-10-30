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

export async function getCarModels(makeId: number) {
  "use cache";
  cacheLife("hours");
  return api.vehicles.models(makeId);
}

export async function getCarModification(id: number) {
  "use cache";
  cacheLife("hours");
  return orNull(api.vehicles.modification(id));
}

export async function getCarModelPage(make: string, model: string) {
  "use cache";
  cacheLife("hours");
  return orNull(api.vehicles.modelPage(make, model));
}

/** Все модели всех марок — для страницы /cars и перелинковки. */
export async function getAllCarModels() {
  "use cache";
  cacheLife("hours");
  const makes = await api.vehicles.makes();
  return Promise.all(makes.map(async (make) => ({ make, models: await api.vehicles.models(make.id) })));
}

export async function getSitemapData() {
  "use cache";
  cacheLife("hours");
  return api.sitemap.index();
}

export async function getSitemapProducts(page: number) {
  "use cache";
  cacheLife("hours");
  return api.sitemap.products(page);
}

/** Реквизиты продавца и список документов — подвал сайта, страницы документов. */
export async function getShopInfo() {
  "use cache";
  cacheLife("hours");
  cacheTag("shop");
  return api.legal.shop();
}

export async function getLegalDocument(slug: string) {
  "use cache";
  cacheLife("hours");
  cacheTag("shop");
  return orNull(api.legal.document(slug));
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

export async function getCategoryProducts(slug: string, modification?: number, page?: number, model?: number) {
  "use cache";
  cacheLife("minutes");
  return orNull(api.catalog.categoryProducts(slug, { modification, model, page }));
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
