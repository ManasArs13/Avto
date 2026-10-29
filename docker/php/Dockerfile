# Laravel (API + Filament). Контекст сборки — корень репозитория.
#   target app   — PHP-FPM; этот же образ запускает очередь и планировщик
#   target nginx — nginx со статикой Laravel (ассеты Filament)

FROM php:8.4-fpm-alpine AS app

# Установщик расширений: собирает зависимости и чистит за собой.
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql intl zip bcmath pcntl opcache redis gd

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /var/www/html

# Сначала только зависимости — слой кэшируется, пока не менялся composer.lock.
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist \
    && composer clear-cache

COPY backend/ ./
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi \
    && php artisan filament:assets \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/private \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]


FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
