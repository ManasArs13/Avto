// Контракт API v1 (backend/routes/api.php). Меняете ответ на бэке — меняйте и здесь.

export interface Brand {
  id: number;
  name: string;
  slug: string;
  country: string | null;
  is_oem: boolean;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  children?: Category[];
}

export interface Offer {
  id: number;
  product_id: number;
  price: number;
  quantity: number;
  multiplicity: number;
  delivery_days: number;
  /** На собственном складе магазина — можно забрать сегодня. */
  in_stock: boolean;
}

export interface Product {
  id: number;
  article: string;
  name: string;
  brand?: Brand;
  category?: Category;
  description: string | null;
  specs: Record<string, string>;
  images: string[];
  min_price?: number | null;
  min_delivery_days?: number | null;
  offers_count?: number;
  offers?: Offer[];
}

export interface ProductDetails {
  product: Product & { offers: Offer[] };
  analogs: (Product & { offers: Offer[] })[];
}

export interface CarMake {
  id: number;
  name: string;
  slug: string;
}

export interface CarModel {
  id: number;
  name: string;
  slug: string;
  year_from: number | null;
  year_to: number | null;
}

export interface CarModification {
  id: number;
  name: string;
  engine_code: string | null;
  engine_volume: string | null;
  power_hp: number | null;
  fuel: string | null;
  year_from: number | null;
  year_to: number | null;
}

export interface CarModificationDetails {
  id: number;
  name: string;
  title: string;
  make: { id: number; name: string };
  model: { id: number; name: string };
}

export interface Labeled<T extends string = string> {
  value: T;
  label: string;
}

export type DeliveryMethod = "pickup" | "courier" | "cdek";
export type PaymentMethod = "card" | "on_receipt" | "invoice";

export interface OrderItem {
  product_id: number | null;
  brand: string;
  article: string;
  name: string;
  price: number;
  quantity: number;
  delivery_days: number;
  status: Labeled;
}

export interface Order {
  number: string;
  created_at: string;
  status: Labeled;
  payment_status: Labeled;
  contact_name: string;
  contact_phone: string;
  contact_email: string | null;
  delivery_method: Labeled<DeliveryMethod>;
  delivery_address: string | null;
  payment_method: Labeled<PaymentMethod>;
  subtotal: number;
  delivery_cost: number;
  total: number;
  comment: string | null;
  items?: OrderItem[];
}

export interface CreateOrderPayload {
  items: { offer_id: number; quantity: number }[];
  contact_name: string;
  contact_phone: string;
  contact_email?: string;
  delivery_method: DeliveryMethod;
  delivery_address?: string;
  payment_method: PaymentMethod;
  comment?: string;
}

export interface Customer {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  discount_percent: string;
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number; per_page: number };
}
