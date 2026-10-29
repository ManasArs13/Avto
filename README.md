# Avto — интернет-магазин автозапчастей

Монорепозиторий: API и админка на Laravel, витрина на Next.js, общий TypeScript-пакет для будущего мобильного приложения.

```
backend/            Laravel 13 + Filament 5 — REST API (/api/v1) и админка (/admin)
web/                Next.js 16 (App Router, Cache Components) — витрина
packages/shared/    @avto/shared — типы API, клиент, форматирование (для web и mobile)
mobile/             (будет) Expo / React Native — подключит @avto/shared
docker-compose.yml  PostgreSQL 17, Redis 7, Meilisearch
```

## Запуск локально

Требуется: PHP 8.3+ (расширения pdo_pgsql, intl, zip), Composer, Node 22+, pnpm, Docker.

```bash
docker compose up -d
```

```bash
cd backend && composer install && cp .env.example .env && php artisan key:generate
```

```bash
cd backend && php artisan migrate --seed && php artisan scout:sync-index-settings && php artisan scout:import "App\Models\Product"
```

```bash
cd backend && composer dev
```

```bash
pnpm install && pnpm dev:web
```

| Что | Адрес |
|---|---|
| Витрина | http://localhost:3001 |
| Админка | http://localhost:8000/admin (учётка — в `backend/database/seeders/DatabaseSeeder.php`) |
| API | http://localhost:8000/api/v1 |
| Meilisearch | http://localhost:7701 |

Порты БД/Redis/Meilisearch сдвинуты (5434, 6380, 7701), чтобы не конфликтовать с другими проектами.

`composer dev` поднимает сервер, **воркер очереди** (нужен для импорта прайсов и индексации поиска) и логи. Витрина при сборке (`next build`) обращается к API, поэтому бэкенд должен быть запущен.

Если Composer не установлен глобально: `php tools/composer.phar …` (скачивается с getcomposer.org, в git не хранится).

## Как устроено

### Каталог и поиск
- **Нормализация артикулов** (`App\Support\Article`): `04465-33450`, `0446533450`, `04465 33450` и кириллица-двойник (`С` → `C`) дают один ключ `article_normalized`.
- **Поиск в два шага**, как принято в автозапчастях: по артикулу находим все бренды, у которых он есть → пользователь выбирает бренд → карточка с предложениями и **аналогами** (таблица `crosses`, связь хранится в обе стороны).
- Если по артикулу пусто — полнотекстовый поиск по названию через **Meilisearch** (Laravel Scout), с опечатками.
- **Подбор по авто**: марка → модель → модификация, таблица применимости `car_modification_product`. В проде данные обычно покупают у TecDoc / Laximo.

### Поставщики, прайсы, цены
- `offers` — товар у конкретного поставщика: закупка, остаток, срок.
- **Импорт CSV-прайса** (`App\Services\PriceImporter`): пачками через `insertOrIgnore` / `upsert`, бренды сопоставляются по алиасам (`TOYOTA/LEXUS` → TOYOTA, `KYB` → KAYABA), кодировка и колонки настраиваются у поставщика. Позиции, пропавшие из нового прайса, удаляются.
  - из админки: Поставщики → поставщик → «Загрузить прайс»;
  - из консоли / cron: `php artisan prices:import supplier-a database/data/demo-price.csv [--sync]`.
- **Наценки** (`App\Services\PriceCalculator`): правила по поставщику, бренду и диапазону закупочной цены с приоритетом; итог округляется вверх до рубля. Покупателю поставщик не раскрывается: в API видны только цена, срок и «в наличии / под заказ».

### Заказы
- Корзина хранится на клиенте, при оформлении сервер **заново считает цены** по `offer_id` и проверяет остатки.
- Гость может оформить заказ и посмотреть его по номеру + телефону; покупатель с аккаунтом — через Bearer-токен (Sanctum). Тот же механизм подойдёт мобильному приложению.
- В позициях заказа сохраняется снимок (бренд, артикул, цена закупки/продажи) — маржа видна в админке.

## API v1

| Метод | Путь | |
|---|---|---|
| GET | `/brands`, `/categories` | справочники |
| GET | `/search?q=` | поиск по артикулу/названию |
| GET | `/products/{id}` | товар + предложения + аналоги |
| GET | `/categories/{slug}/products?modification=&page=` | товары категории (с фильтром по авто) |
| GET | `/vehicles/makes`, `/vehicles/makes/{id}/models`, `/vehicles/models/{id}/modifications`, `/vehicles/modifications/{id}` | подбор по авто |
| POST | `/auth/register`, `/auth/login` | токен покупателя |
| GET/POST | `/auth/me`, `/auth/logout` | 🔒 |
| POST | `/orders` | оформление (гость или 🔒) |
| GET | `/orders` | 🔒 мои заказы |
| GET | `/orders/{number}?phone=` | заказ по номеру |

Типы ответов — `packages/shared/src/types.ts`.

## Что дальше

- [ ] Мобильное приложение: `pnpm create expo-app mobile`, подключить `@avto/shared`, токен — в SecureStore
- [ ] Личный кабинет на сайте (логин, история заказов, «мой гараж»)
- [ ] Оплата: ЮKassa / CloudPayments (вебхук → `payment_status`), чеки 54-ФЗ
- [ ] Доставка: расчёт через API СДЭК вместо фиксированных тарифов в `OrderService`
- [ ] Подключение каталогов TecDoc / Laximo (поиск по VIN, реальные кроссы и применимость)
- [ ] Онлайн-опрос API поставщиков (тип «API поставщика»): `Http::pool()` + кэш в Redis
- [ ] Роли в админке (spatie/laravel-permission), русские сообщения валидации (laravel-lang)
- [ ] Обмен с 1С (CommerceML), уведомления о заказе (email / Telegram)
- [ ] Тесты: Pest для API и импорта, Playwright для оформления заказа
