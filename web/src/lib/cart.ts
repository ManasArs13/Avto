"use client";

import { useSyncExternalStore } from "react";

// Корзина в localStorage. Цены здесь только для отображения —
// при оформлении сервер пересчитывает всё сам по offer_id.

export interface CartItem {
  offerId: number;
  productId: number;
  brand: string;
  article: string;
  name: string;
  price: number;
  quantity: number;
  maxQuantity: number;
  deliveryDays: number;
}

const KEY = "avto.cart.v1";
const EMPTY: CartItem[] = [];
const listeners = new Set<() => void>();

let cachedRaw: string | null = null;
let cachedItems: CartItem[] = EMPTY;

function read(): CartItem[] {
  let raw: string | null = null;
  try {
    raw = localStorage.getItem(KEY);
  } catch {
    return EMPTY;
  }
  if (raw !== cachedRaw) {
    cachedRaw = raw;
    try {
      cachedItems = raw ? JSON.parse(raw) : EMPTY;
    } catch {
      cachedItems = EMPTY;
    }
  }
  return cachedItems;
}

function write(items: CartItem[]) {
  try {
    localStorage.setItem(KEY, JSON.stringify(items));
  } catch {
    // Приватный режим / заблокированное хранилище — корзина просто не сохранится.
  }
  listeners.forEach((l) => l());
}

function subscribe(listener: () => void) {
  listeners.add(listener);
  // Синхронизация между вкладками.
  const onStorage = (e: StorageEvent) => e.key === KEY && listener();
  window.addEventListener("storage", onStorage);
  return () => {
    listeners.delete(listener);
    window.removeEventListener("storage", onStorage);
  };
}

export const cart = {
  add(item: Omit<CartItem, "quantity">, quantity = 1) {
    const items = read();
    const existing = items.find((i) => i.offerId === item.offerId);
    write(
      existing
        ? items.map((i) =>
            i.offerId === item.offerId ? { ...i, quantity: Math.min(i.quantity + quantity, i.maxQuantity) } : i,
          )
        : [...items, { ...item, quantity: Math.min(quantity, item.maxQuantity) }],
    );
  },
  setQuantity(offerId: number, quantity: number) {
    write(
      read()
        .map((i) => (i.offerId === offerId ? { ...i, quantity: Math.max(0, Math.min(quantity, i.maxQuantity)) } : i))
        .filter((i) => i.quantity > 0),
    );
  },
  remove(offerId: number) {
    write(read().filter((i) => i.offerId !== offerId));
  },
  clear() {
    write([]);
  },
};

export function useCart() {
  const items = useSyncExternalStore(subscribe, read, () => EMPTY);
  const count = items.reduce((sum, i) => sum + i.quantity, 0);
  const total = items.reduce((sum, i) => sum + i.price * i.quantity, 0);
  return { items, count, total };
}
