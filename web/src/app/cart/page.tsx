"use client";

import { formatDelivery, formatPrice } from "@avto/shared";
import Link from "next/link";
import { cart, useCart } from "@/lib/cart";

export default function CartPage() {
  const { items, total } = useCart();

  if (items.length === 0) {
    return (
      <div className="card mx-auto max-w-md p-8 text-center">
        <h1 className="text-xl font-bold">Корзина пуста</h1>
        <p className="mt-2 text-muted">Найдите деталь по артикулу или подберите по автомобилю.</p>
        <Link href="/" className="btn mt-5">
          На главную
        </Link>
      </div>
    );
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
      <div className="space-y-2">
        <h1 className="mb-2 text-2xl font-bold">Корзина</h1>
        {items.map((item) => (
          <div key={item.offerId} className="card flex flex-wrap items-center gap-x-5 gap-y-3 p-4">
            <Link href={`/product/${item.productId}`} className="min-w-48 flex-1 hover:text-accent">
              <div className="text-sm font-bold">
                {item.brand} <span className="article">{item.article}</span>
              </div>
              <div className="text-sm text-muted">{item.name}</div>
              <div className="text-xs text-muted">Срок: {formatDelivery(item.deliveryDays)}</div>
            </Link>
            <div className="flex items-center gap-1">
              <button
                type="button"
                className="btn-ghost h-9 w-9 px-0"
                onClick={() => cart.setQuantity(item.offerId, item.quantity - 1)}
                aria-label="Меньше"
              >
                −
              </button>
              <span className="w-10 text-center tabular-nums">{item.quantity}</span>
              <button
                type="button"
                className="btn-ghost h-9 w-9 px-0"
                onClick={() => cart.setQuantity(item.offerId, item.quantity + 1)}
                disabled={item.quantity >= item.maxQuantity}
                aria-label="Больше"
              >
                +
              </button>
            </div>
            <div className="w-28 text-right font-bold tabular-nums">{formatPrice(item.price * item.quantity)}</div>
            <button
              type="button"
              className="text-sm text-muted hover:text-red-600"
              onClick={() => cart.remove(item.offerId)}
            >
              Удалить
            </button>
          </div>
        ))}
      </div>

      <aside className="card h-fit space-y-4 p-5 lg:sticky lg:top-24">
        <div className="flex justify-between text-lg">
          <span>Итого</span>
          <span className="font-bold tabular-nums">{formatPrice(total)}</span>
        </div>
        <p className="text-xs text-muted">Окончательная цена фиксируется при оформлении заказа.</p>
        <Link href="/checkout" className="btn w-full py-3">
          Оформить заказ
        </Link>
      </aside>
    </div>
  );
}
