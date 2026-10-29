"use client";

import Link from "next/link";
import { useSession } from "@/lib/auth";

export function AccountLink() {
  const session = useSession();

  return (
    <Link
      href={session ? "/account" : "/account/login"}
      className="btn-ghost shrink-0"
      aria-label={session ? "Личный кабинет" : "Войти"}
    >
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
        <circle cx="12" cy="8" r="4" />
        <path d="M4 21a8 8 0 0 1 16 0" />
      </svg>
      <span className="hidden max-w-28 truncate sm:inline">{session ? session.customer.name : "Войти"}</span>
    </Link>
  );
}
