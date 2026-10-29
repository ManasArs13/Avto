import { createApiClient } from "@avto/shared";

// Браузер ходит по публичному адресу (в Docker — относительный /api/v1 через nginx).
// Сервер Next.js может ходить по внутреннему адресу сети контейнеров (API_INTERNAL_URL).
const PUBLIC_API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

export const API_URL =
  (typeof window === "undefined" ? process.env.API_INTERNAL_URL : undefined) ?? PUBLIC_API_URL;

/** Клиент без авторизации: каталог, поиск, гостевые заказы. */
export const api = createApiClient({ baseUrl: API_URL });
