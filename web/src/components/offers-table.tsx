import { formatDelivery, formatPrice, type Offer, type Product } from "@avto/shared";
import { AddToCart } from "./add-to-cart";

export function OffersTable({ product, offers }: { product: Product; offers: Offer[] }) {
  if (offers.length === 0) {
    return <p className="px-4 py-3 text-sm text-muted">Нет в наличии у поставщиков.</p>;
  }

  return (
    <ul className="divide-y divide-border">
      {offers.map((offer) => (
        <li key={offer.id} className="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
          <div className="min-w-32 flex-1">
            {offer.in_stock ? (
              <span className="text-sm font-semibold text-success">В наличии на складе</span>
            ) : (
              <span className="text-sm">Под заказ · {formatDelivery(offer.delivery_days)}</span>
            )}
            <div className="text-xs text-muted">Доступно: {offer.quantity} шт.</div>
          </div>
          <div className="text-lg font-bold tabular-nums">{formatPrice(offer.price)}</div>
          <AddToCart product={product} offer={offer} />
        </li>
      ))}
    </ul>
  );
}
