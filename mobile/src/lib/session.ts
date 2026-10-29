import type { Customer } from '@avto/shared';
import * as SecureStore from 'expo-secure-store';
import { useSyncExternalStore } from 'react';

// Вход покупателя. Токен Sanctum хранится в защищённом хранилище ОС (Keychain / Keystore).

export interface Session {
  token: string;
  customer: Customer;
}

const KEY = 'avto.session';
const listeners = new Set<() => void>();
let current: Session | null = null;

export function getToken() {
  return current?.token;
}

/** Читаем сохранённую сессию при запуске приложения. */
export async function restoreSession() {
  const raw = await SecureStore.getItemAsync(KEY).catch(() => null);
  try {
    current = raw ? JSON.parse(raw) : null;
  } catch {
    current = null;
  }
  listeners.forEach((l) => l());
}

export async function setSession(session: Session | null) {
  current = session;
  listeners.forEach((l) => l());
  if (session) await SecureStore.setItemAsync(KEY, JSON.stringify(session));
  else await SecureStore.deleteItemAsync(KEY);
}

export function useSession() {
  return useSyncExternalStore(
    (l) => {
      listeners.add(l);
      return () => listeners.delete(l);
    },
    () => current,
  );
}
