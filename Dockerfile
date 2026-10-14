FROM serversideup/php:8.3-fpm-nginx-alpine AS base

ENV AUTORUN_ENABLED=1 \
    PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off

USER root

RUN apk add --update busybox-suid && \
    install-php-extensions bcmath gd exif intl zip pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

USER www-data

# ---- JS assets (Vite 8 needs a recent Node) ----
# vendor/ is copied in because app.css imports Flux CSS and @source-scans
# vendor stub paths — Tailwind/Vite need composer deps present to build.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=dependencies /var/www/html/vendor ./vendor
RUN npm ci && npm run build

# ---- Composer dependencies (prod only) ----
FROM base AS dependencies
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-dev --optimize-autoloader

# ---- Final image ----
FROM base
WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html
COPY --from=dependencies --chown=www-data:www-data /var/www/html/vendor /var/www/html/vendor
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

RUN rm -rf /usr/bin/composer && \
    mkdir -p /storage && chown www-data:www-data /storage
