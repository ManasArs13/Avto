import { formatDelivery, formatPrice, type Product } from "@avto/shared";
import Link from "next/link";

export function ProductRow({ product }: { product: Product }) {
  const available = (product.offers_count ?? 0) > 0;

  return (
    <Link
      href={`/product/${product.id}`}
      className="card flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 transition-colors hover:border-accent"
    >
      <div className="w-32 shrink-0">
        <div className="text-sm font-bold">{product.brand?.name}</div>
        {product.brand?.is_oem && <div className="text-xs text-accent">оригинал</div>}
      </div>
      <div className="min-w-48 flex-1">
        <div className="article">{product.article}</div>
        <div className="text-sm text-muted">{product.name}</div>
      </div>
      {available ? (
        <div className="text-right">
          <div className="text-lg font-bold tabular-nums">от {formatPrice(product.min_price)}</div>
          <div className="text-xs text-muted">
            {product.offers_count} предл. · от {formatDelivery(product.min_delivery_days)}
          </div>
        </div>
      ) : (
        <div className="text-sm text-muted">нет в наличии</div>
      )}
    </Link>
  );
}
