FROM serversideup/php:8.3-fpm-nginx-alpine AS base

ENV AUTORUN_ENABLED=1 \
    PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off

USER root

RUN apk add --update busybox-suid && \
    install-php-extensions bcmath gd exif intl zip pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

USER www-data

# ---- Composer dependencies (prod only) ----
# Must come before "assets" because the assets build copies vendor/ from here
# (app.css imports Flux CSS and @source-scans vendor stub paths).
# Auth for livewire/flux-pro: auth.json lives ONLY on the VPS (baked into the
# local composer-auth:local image, built from /root/composer-auth). It is
# copied in here, used by composer, and deleted in the same layer — it never
# reaches the final image and is never committed to git.
FROM base AS dependencies
WORKDIR /var/www/html
COPY composer.json composer.lock ./
COPY --from=composer-auth:local /auth.json ./auth.json
RUN composer install --no-interaction --prefer-dist --no-scripts --no-dev --optimize-autoloader && rm -f auth.json

# ---- JS assets (Vite 8 needs a recent Node) ----
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=dependencies /var/www/html/vendor ./vendor
RUN npm ci && npm run build

# ---- Final image ----
FROM base
WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html
COPY --from=dependencies --chown=www-data:www-data /var/www/html/vendor /var/www/html/vendor
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

RUN rm -rf /usr/bin/composer && \
    mkdir -p /storage && chown www-data:www-data /storage
