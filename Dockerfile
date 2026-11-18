FROM php:8.4-fpm-bookworm AS app

ARG APP_ENV=local

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql bcmath zip pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_PROCESS_TIMEOUT=600

COPY . .

RUN composer config -g process-timeout 600 \
    && composer install --no-interaction --prefer-dist --no-progress \
    && mkdir -p storage/framework/cache \
       storage/framework/sessions \
       storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -sfn ../storage/app/public public/storage

CMD ["php-fpm"]

FROM nginx:alpine AS web

COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
