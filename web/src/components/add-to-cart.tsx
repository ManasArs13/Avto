"use client";

import type { Offer, Product } from "@avto/shared";
import { cart, useCart } from "@/lib/cart";

export function AddToCart({ product, offer }: { product: Product; offer: Offer }) {
  const { items } = useCart();
  const inCart = items.find((i) => i.offerId === offer.id)?.quantity ?? 0;

  return (
    <button
      type="button"
      className={inCart > 0 ? "btn-ghost whitespace-nowrap" : "btn whitespace-nowrap"}
      disabled={inCart >= offer.quantity}
      onClick={() =>
        cart.add(
          {
            offerId: offer.id,
            productId: product.id,
            brand: product.brand?.name ?? "",
            article: product.article,
            name: product.name,
            price: offer.price,
            maxQuantity: offer.quantity,
            deliveryDays: offer.delivery_days,
          },
          offer.multiplicity,
        )
      }
    >
      {inCart > 0 ? `В корзине: ${inCart}` : "В корзину"}
    </button>
  );
}
