import { ApiError, formatDelivery, formatPrice } from "@avto/shared";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { api } from "@/lib/api";

export const metadata: Metadata = { title: "Заказ", robots: { index: false } };

export default function OrderPage(props: PageProps<"/order/[number]">) {
  return (
    <Suspense fallback={<div className="card h-64 animate-pulse" />}>
      <OrderDetails params={props.params} searchParams={props.searchParams} />
    </Suspense>
  );
}

// Персональные данные — без кэша, каждый раз свежий запрос.
async function OrderDetails({ params, searchParams }: PageProps<"/order/[number]">) {
  const { number } = await params;
  const { phone } = await searchParams;

  const order = await api.orders.show(number, typeof phone === "string" ? phone : undefined).catch((e) => {
    if (e instanceof ApiError && e.status === 404) return null;
    throw e;
  });
  if (!order) notFound();

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="card p-6">
        <div className="text-sm text-muted">Заказ</div>
        <h1 className="article text-3xl">№ {order.number}</h1>
        <div className="mt-3 flex flex-wrap gap-2 text-sm">
          <span className="rounded-lg bg-accent/10 px-3 py-1 font-semibold text-accent">{order.status.label}</span>
          <span className="rounded-lg border border-border px-3 py-1">{order.payment_status.label}</span>
        </div>
        <p className="mt-4 text-sm text-muted">
          Мы свяжемся с вами по телефону {order.contact_phone} для подтверждения. Сохраните номер заказа.
        </p>
      </div>

      <div className="card divide-y divide-border">
        {order.items?.map((item, i) => (
          <div key={i} className="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3">
            <div className="min-w-48 flex-1">
              <div className="text-sm font-bold">
                {item.brand} <span className="article">{item.article}</span>
              </div>
              <div className="text-sm text-muted">{item.name}</div>
            </div>
            <div className="text-sm text-muted">{formatDelivery(item.delivery_days)}</div>
            <div className="text-sm tabular-nums">
              {item.quantity} × {formatPrice(item.price)}
            </div>
          </div>
        ))}
        <div className="space-y-1 px-5 py-4 text-sm">
          <Row label="Товары" value={formatPrice(order.subtotal)} />
          <Row label={`Доставка: ${order.delivery_method.label}`} value={formatPrice(order.delivery_cost)} />
          {order.delivery_address && <Row label="Адрес" value={order.delivery_address} />}
          <Row label="Оплата" value={order.payment_method.label} />
          <div className="flex justify-between pt-2 text-lg font-bold">
            <span>Итого</span>
            <span className="tabular-nums">{formatPrice(order.total)}</span>
          </div>
        </div>
      </div>
    </div>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4">
      <span className="text-muted">{label}</span>
      <span className="text-right">{value}</span>
    </div>
  );
}
