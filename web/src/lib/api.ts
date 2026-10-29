import { createApiClient } from "@avto/shared";

export const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

/** Клиент без авторизации: каталог, поиск, гостевые заказы. */
export const api = createApiClient({ baseUrl: API_URL });
