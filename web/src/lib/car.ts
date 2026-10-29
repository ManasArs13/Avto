"use client";

import { useSyncExternalStore } from "react";

// Выбранный автомобиль покупателя («мой гараж» в минимальном виде).

export interface SelectedCar {
  id: number;
  title: string;
}

const KEY = "avto.car.v1";
const listeners = new Set<() => void>();
let cachedRaw: string | null = null;
let cached: SelectedCar | null = null;

function read(): SelectedCar | null {
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

export function setSelectedCar(car: SelectedCar | null) {
  try {
    if (car) localStorage.setItem(KEY, JSON.stringify(car));
    else localStorage.removeItem(KEY);
  } catch {
    // ignore
  }
  listeners.forEach((l) => l());
}

export function useSelectedCar() {
  return useSyncExternalStore(
    (l) => {
      listeners.add(l);
      return () => listeners.delete(l);
    },
    read,
    () => null,
  );
}
