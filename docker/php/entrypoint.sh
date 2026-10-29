#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY не задан. Сгенерируйте: docker compose ... run --rm app php artisan key:generate --show" >&2
    exit 1
fi

# Кэш конфига/маршрутов/вьюх собираем при старте: переменные окружения известны только сейчас.
php artisan optimize --quiet
php artisan filament:optimize --quiet || true

# Миграции запускает только основной контейнер (RUN_MIGRATIONS=true), чтобы не было гонок.
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force --no-interaction
    php artisan scout:sync-index-settings --quiet || echo "Meilisearch недоступен — настройки индекса не применены" >&2
fi

exec "$@"
