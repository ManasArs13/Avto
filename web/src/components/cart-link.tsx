"use client";

import Link from "next/link";
import { useCart } from "@/lib/cart";

export function CartLink() {
  const { count } = useCart();

  return (
    <Link href="/cart" className="btn-ghost relative shrink-0" aria-label={`Корзина, товаров: ${count}`}>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
        <circle cx="9" cy="21" r="1" />
        <circle cx="20" cy="21" r="1" />
        <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6" />
      </svg>
      <span className="hidden sm:inline">Корзина</span>
      {count > 0 && (
        <span className="absolute -top-2 -right-2 min-w-5 rounded-full bg-accent px-1.5 text-center text-xs font-bold text-white">
          {count}
        </span>
      )}
    </Link>
  );
}
