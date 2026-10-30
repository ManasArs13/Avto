"use client";

import Link from "next/link";
import { useSyncExternalStore } from "react";

// Уведомление о cookie. Аналитики пока нет, поэтому это информирование, а не запрос согласия;
// при подключении Яндекс Метрики и т.п. её нужно запускать только после нажатия «Принять».

const KEY = "avto.cookies.v1";
const listeners = new Set<() => void>();

function accepted() {
  try {
    return localStorage.getItem(KEY) === "1";
  } catch {
    return true;
  }
}

function accept() {
  try {
    localStorage.setItem(KEY, "1");
  } catch {
    // ignore
  }
  listeners.forEach((l) => l());
}

export function CookieNotice() {
  const hidden = useSyncExternalStore(
    (l) => {
      listeners.add(l);
      return () => listeners.delete(l);
    },
    accepted,
    () => true,
  );

  if (hidden) return null;

  return (
    <div className="fixed inset-x-0 bottom-0 z-20 p-3 sm:p-4">
      <div className="card mx-auto flex max-w-3xl flex-wrap items-center gap-3 p-4 text-sm shadow-lg">
        <p className="min-w-0 flex-1">
          Мы используем cookie и локальное хранилище браузера, чтобы работали корзина, выбор автомобиля и личный
          кабинет. Подробнее — в{" "}
          <Link href="/legal/privacy" className="text-accent hover:underline">
            Политике обработки персональных данных
          </Link>
          .
        </p>
        <button type="button" className="btn" onClick={accept}>
          Понятно
        </button>
      </div>
    </div>
  );
}
