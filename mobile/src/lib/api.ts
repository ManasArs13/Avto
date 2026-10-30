import { createApiClient } from '@avto/shared';
import * as WebBrowser from 'expo-web-browser';

import { getToken } from './session';

/**
 * Адрес API задаётся при сборке: EXPO_PUBLIC_API_URL в mobile/.env.
 * Эмулятор Android видит компьютер разработчика как 10.0.2.2, телефон в той же Wi‑Fi — по IP компьютера.
 */
export const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1';

/** Тот же клиент, что на сайте; токен покупателя подставляется, если он вошёл. */
export const api = createApiClient({ baseUrl: API_URL, getToken });

/** Сайт магазина: документы (политика ПДн, оферта…) открываем оттуда, чтобы тексты были в одном месте. */
export const SITE_URL = (process.env.EXPO_PUBLIC_SITE_URL ?? 'http://localhost:3001').replace(/\/$/, '');

export function openDocument(slug: string) {
  return WebBrowser.openBrowserAsync(`${SITE_URL}/legal/${slug}`);
}
