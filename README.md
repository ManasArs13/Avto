# Avto — интернет-магазин автозапчастей

Монорепозиторий: API и админка на Laravel, витрина на Next.js, общий TypeScript-пакет для будущего мобильного приложения.

```
backend/                 Laravel 13 + Filament 5 — REST API (/api/v1) и админка (/admin)
web/                     Next.js 16 (App Router, Cache Components) — витрина
packages/shared/         @avto/shared — типы API, клиент, форматирование (для web и mobile)
mobile/                  (будет) Expo / React Native — подключит @avto/shared
docker-compose.yml       локальная разработка: PostgreSQL, Redis, Meilisearch
docker-compose.prod.yml  полный стек в контейнерах (nginx, PHP-FPM, очередь, Next.js, БД)
docker/                  Dockerfile-ы и конфиги nginx / PHP
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

## Запуск в Docker (продакшен)

Весь стек в контейнерах — `docker-compose.prod.yml`:

```
            :8080
              │
           nginx ──── /api, /admin, Livewire, ассеты Filament ──► app (PHP-FPM)
              │                                                   queue (очередь: прайсы, индексация)
              └─────── всё остальное ──► web (Next.js)            scheduler (cron Laravel)
                                                                  postgres · redis · meilisearch
```

Сайт и API на одном домене, поэтому CORS не нужен. Next.js на сервере ходит в API по внутренней сети (`http://nginx/api/v1`), браузер — по относительному `/api/v1`.

```bash
cp .env.docker.example .env.docker
```

Заполните в `.env.docker` пароли и `APP_KEY` (сгенерировать: `php -r "echo 'base64:'.base64_encode(random_bytes(32));"`), затем:

```bash
pnpm docker:up
```

Первый запуск: миграции выполнятся сами (контейнер `app`, `RUN_MIGRATIONS=true`). Демо-данные и пользователь админки:

```bash
pnpm docker:artisan db:seed --force
```

```bash
pnpm docker:artisan make:filament-user
```

Сайт — http://localhost:8080, админка — http://localhost:8080/admin. Логи: `pnpm docker:logs`, остановка: `pnpm docker:down`.

| Образ | Что внутри |
|---|---|
| `avto-php` (`docker/php/Dockerfile`, target `app`) | PHP 8.4-FPM Alpine, pdo_pgsql, intl, redis, gd, opcache; composer без dev-зависимостей; запускается от `www-data` |
| `avto-nginx` (target `nginx`) | nginx + статика Laravel/Filament из того же сборочного этапа |
| `avto-web` (`docker/web/Dockerfile`) | Next.js standalone-сервер на Node 22 |

Заметки для продакшена:
- HTTPS — внешним прокси (Caddy / Traefik / балансировщик облака) перед nginx; Laravel доверяет заголовкам `X-Forwarded-*`. Укажите `APP_URL=https://…`.
- Тома: `storage` (загруженные прайсы), `pgdata`, `redisdata`, `meilidata` — их и бэкапить.
- Кэш конфигурации и маршрутов собирается при старте контейнера, поэтому после правки `.env.docker` достаточно перезапуска.
- Livewire по умолчанию принимает загрузки до 12 МБ — для больших прайсов поднимите `temporary_file_upload.rules` в `config/livewire.php`.

## Как устроено

### Каталог и поиск
- **Нормализация артикулов** (`App\Support\Article`): `21080-1012005-08`, `21080101200508`, `21080 1012005 08` и кириллица-двойник (`С` → `C`) дают один ключ `article_normalized`.
- **Номера ВАЗ в любом формате**: старый `2108-3501080`, новый `21080-3501080` и с суффиксом исполнения `21080-3501080-00` находят одну деталь (`Article::vazStem`). Для поиска по началу номера в PostgreSQL есть индекс `varchar_pattern_ops`.
- **Поиск в два шага**, как принято в автозапчастях: по артикулу находим все бренды, у которых он есть → пользователь выбирает бренд → карточка с предложениями и **аналогами** (таблица `crosses`, связь хранится в обе стороны).
- Если по артикулу пусто — полнотекстовый поиск по названию через **Meilisearch** (Laravel Scout), с опечатками.
- **Подбор по авто**: модель LADA → двигатель (марка одна, поэтому её выбор скрыт), таблица применимости `car_modification_product`. В проде применимость берут из каталога LADA (например, Laximo) или от поставщика.
- **Демо-каталог** (`DemoCatalogSeeder`): 10 моделей LADA от 2107 до Vesta, 24 оригинальные позиции ВАЗ с аналогами (MANN, BOSCH, NGK, FENOX, LUZAR, TRIALLI, SS20 и др.). Номера построены по схеме каталога ВАЗ, но кроссы, применимость и цены условные.

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
- [ ] Тесты: импорт прайсов и заказы (поиск и нормализация уже покрыты — `php artisan test`), Playwright для оформления заказа
