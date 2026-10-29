"use client";

import { ApiError, createApiClient, type Customer } from "@avto/shared";
import { useSyncExternalStore } from "react";
import { API_URL } from "./api";

// Вход покупателя: Bearer-токен Sanctum в localStorage.
// Страницы кабинета — клиентские и не индексируются, поэтому cookie и SSR здесь не нужны.

export interface Session {
  token: string;
  customer: Customer;
}

const KEY = "avto.auth.v1";
const listeners = new Set<() => void>();
let cachedRaw: string | null = null;
let cached: Session | null = null;

function read(): Session | null {
  let raw: string | null = null;
  try {
    raw = localStorage.getItem(KEY);
  } catch {
    return null;
  }
  if (raw !== cachedRaw) {
    cachedRaw = raw;
    try {
      cached = raw ? JSON.parse(raw) : null;
    } catch {
      cached = null;
    }
  }
  return cached;
}

export function setSession(session: Session | null) {
  try {
    if (session) localStorage.setItem(KEY, JSON.stringify(session));
    else localStorage.removeItem(KEY);
  } catch {
    // ignore
  }
  listeners.forEach((l) => l());
}

export function updateCustomer(customer: Customer) {
  const session = read();
  if (session) setSession({ ...session, customer });
}

/** Клиент API с токеном покупателя (если он вошёл). */
export const authApi = createApiClient({ baseUrl: API_URL, getToken: () => read()?.token });

/** Токен отозван или истёк — выходим локально. Возвращает true, если ошибка была 401. */
export function handleUnauthorized(e: unknown): boolean {
  if (e instanceof ApiError && e.status === 401) {
    setSession(null);
    return true;
  }
  return false;
}

export async function logout() {
  await authApi.auth.logout().catch(() => undefined);
  setSession(null);
}

const subscribe = (l: () => void) => {
  listeners.add(l);
  // Вход/выход в другой вкладке.
  const onStorage = (e: StorageEvent) => e.key === KEY && l();
  window.addEventListener("storage", onStorage);
  return () => {
    listeners.delete(l);
    window.removeEventListener("storage", onStorage);
  };
};

export function useSession() {
  return useSyncExternalStore(subscribe, read, () => null);
}

/** Пока страница не гидратирована, сессию не знаем: не показываем «Войти» на долю секунды. */
export function useHydrated() {
  return useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );
}
