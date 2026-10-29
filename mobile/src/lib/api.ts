import { createApiClient } from '@avto/shared';

import { getToken } from './session';

/**
 * Адрес API задаётся при сборке: EXPO_PUBLIC_API_URL в mobile/.env.
 * Эмулятор Android видит компьютер разработчика как 10.0.2.2, телефон в той же Wi‑Fi — по IP компьютера.
 */
export const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1';

/** Тот же клиент, что на сайте; токен покупателя подставляется, если он вошёл. */
export const api = createApiClient({ baseUrl: API_URL, getToken });
