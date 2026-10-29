import type { Offer, Product } from '@avto/shared';
import { useSyncExternalStore } from 'react';

// Корзина в памяти. TODO: сохранять между запусками (expo-sqlite/kv-store или AsyncStorage).

export interface CartItem {
  offerId: number;
  productId: number;
  brand: string;
  article: string;
  name: string;
  price: number;
  quantity: number;
  maxQuantity: number;
}

const listeners = new Set<() => void>();
let items: CartItem[] = [];

function set(next: CartItem[]) {
  items = next;
  listeners.forEach((l) => l());
}

export const cart = {
  add(product: Product, offer: Offer) {
    const existing = items.find((i) => i.offerId === offer.id);
    if (existing) {
      cart.setQuantity(offer.id, existing.quantity + offer.multiplicity);
      return;
    }
    set([
      ...items,
      {
        offerId: offer.id,
        productId: product.id,
        brand: product.brand?.name ?? '',
        article: product.article,
        name: product.name,
        price: offer.price,
        quantity: offer.multiplicity,
        maxQuantity: offer.quantity,
      },
    ]);
  },
  setQuantity(offerId: number, quantity: number) {
    set(
      items
        .map((i) => (i.offerId === offerId ? { ...i, quantity: Math.min(quantity, i.maxQuantity) } : i))
        .filter((i) => i.quantity > 0),
    );
  },
  clear() {
    set([]);
  },
};

export function useCart() {
  const list = useSyncExternalStore(
    (l) => {
      listeners.add(l);
      return () => listeners.delete(l);
    },
    () => items,
  );
  return {
    items: list,
    count: list.reduce((n, i) => n + i.quantity, 0),
    total: list.reduce((sum, i) => sum + i.price * i.quantity, 0),
  };
}
