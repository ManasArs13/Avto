import type {
  Brand,
  CarMake,
  CarModel,
  CarModelPage,
  CarModification,
  CarModificationDetails,
  Category,
  CreateOrderPayload,
  Customer,
  GarageVehicle,
  Order,
  Paginated,
  Product,
  ProductDetails,
  SitemapData,
} from "./types";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    /** Ошибки валидации Laravel: { "поле": ["сообщение"] } */
    public errors: Record<string, string[]> = {},
  ) {
    super(message);
  }
}

export interface ApiClientOptions {
  baseUrl: string;
  /** Токен покупателя (Sanctum). В приложении — из SecureStore, на сайте — из localStorage/cookie. */
  getToken?: () => string | null | undefined;
}

/**
 * Клиент API без зависимостей: работает в Next.js (сервер и браузер) и в React Native.
 */
export function createApiClient({ baseUrl, getToken }: ApiClientOptions) {
  async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
    const token = getToken?.();
    const res = await fetch(`${baseUrl}${path}`, {
      ...init,
      headers: {
        Accept: "application/json",
        ...(init.body ? { "Content-Type": "application/json" } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...init.headers,
      },
    });

    if (!res.ok) {
      const body = await res.json().catch(() => ({}));
      throw new ApiError(res.status, body.message ?? res.statusText, body.errors);
    }

    return res.json() as Promise<T>;
  }

  const get = <T>(path: string) => request<{ data: T }>(path).then((r) => r.data);
  const post = <T>(path: string, body?: unknown) =>
    request<{ data: T }>(path, { method: "POST", body: body ? JSON.stringify(body) : undefined }).then((r) => r.data);

  return {
    catalog: {
      brands: () => get<Brand[]>("/brands"),
      categories: () => get<Category[]>("/categories"),
      categoryProducts: (slug: string, params: { modification?: number; model?: number; page?: number } = {}) =>
        request<Paginated<Product>>(`/categories/${encodeURIComponent(slug)}/products${query(params)}`),
      search: (q: string) => get<Product[]>(`/search${query({ q })}`),
      product: (id: number) => get<ProductDetails>(`/products/${id}`),
    },
    vehicles: {
      makes: () => get<CarMake[]>("/vehicles/makes"),
      models: (makeId: number) => get<CarModel[]>(`/vehicles/makes/${makeId}/models`),
      modifications: (modelId: number) => get<CarModification[]>(`/vehicles/models/${modelId}/modifications`),
      modification: (id: number) => get<CarModificationDetails>(`/vehicles/modifications/${id}`),
      /** По slug: /vehicles/makes/lada/models/vesta */
      modelPage: (make: string, model: string) =>
        get<CarModelPage>(`/vehicles/makes/${encodeURIComponent(make)}/models/${encodeURIComponent(model)}`),
    },
    sitemap: {
      index: () => get<SitemapData>("/sitemap"),
      /** [id, дата изменения] товаров в наличии. */
      products: (page: number) => get<[number, string][]>(`/sitemap/products/${page}`),
    },
    auth: {
      register: (data: { name: string; email: string; password: string; phone?: string; device_name?: string }) =>
        post<{ token: string; customer: Customer }>("/auth/register", data),
      login: (data: { email: string; password: string; device_name?: string }) =>
        post<{ token: string; customer: Customer }>("/auth/login", data),
      logout: () => request<{ message: string }>("/auth/logout", { method: "POST" }),
      me: () => get<Customer>("/auth/me"),
      update: (data: { name?: string; phone?: string | null; current_password?: string; password?: string }) =>
        request<{ data: Customer }>("/auth/me", { method: "PATCH", body: JSON.stringify(data) }).then((r) => r.data),
    },
    garage: {
      list: () => get<GarageVehicle[]>("/garage"),
      /** Повторное добавление той же модификации обновляет подпись и VIN. */
      add: (data: { car_modification_id: number; name?: string; vin?: string }) => post<GarageVehicle>("/garage", data),
      remove: (id: number) => request<{ message: string }>(`/garage/${id}`, { method: "DELETE" }),
    },
    orders: {
      create: (payload: CreateOrderPayload) => post<Order>("/orders", payload),
      list: (page = 1) => request<Paginated<Order>>(`/orders${query({ page })}`),
      /** Гость смотрит заказ по номеру и телефону. */
      show: (number: string, phone?: string) => get<Order>(`/orders/${encodeURIComponent(number)}${query({ phone })}`),
    },
  };
}

export type ApiClient = ReturnType<typeof createApiClient>;

function query(params: Record<string, string | number | undefined>): string {
  const entries = Object.entries(params).filter(([, v]) => v !== undefined && v !== "");
  return entries.length ? "?" + new URLSearchParams(entries.map(([k, v]) => [k, String(v)])).toString() : "";
}
