FROM composer:2 AS composer-deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --ignore-platform-reqs

FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources/ resources/
COPY app/ app/
COPY --from=composer-deps /app/vendor vendor/
RUN npm install --no-audit --no-fund && npm run build

FROM php:8.5-fpm-alpine AS runtime

RUN apk add --no-cache \
    nginx \
    supervisor \
    libpq \
    libzip \
    libxml2 \
    icu-libs \
    oniguruma \
    && apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    zip \
    pcntl \
    intl \
    bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /var/www

COPY . .
COPY --from=composer-deps /app/vendor vendor/
COPY --from=assets /app/public/build public/build/

RUN mkdir -p bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
