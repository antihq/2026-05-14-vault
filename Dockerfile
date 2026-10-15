# Laravel production image, built by Single Server/Kamal on the VPS.
# Stages: base -> dependencies (composer) -> assets (vite) -> runtime.
FROM serversideup/php:8.4-fpm-nginx-alpine AS base

# Single-user tuning: FPM workers spawn on demand and die after 10s idle.
ENV AUTORUN_ENABLED=1 \
    PHP_OPCACHE_ENABLE=1 \
    PHP_MEMORY_LIMIT=128M \
    PHP_FPM_PM_MAX_CHILDREN=4 \
    PHP_FPM_PM_MAX_REQUESTS=500 \
    SSL_MODE=off

USER root
RUN apk add --update busybox-suid && \
    install-php-extensions bcmath gd exif intl zip pdo_sqlite
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
USER www-data

# Composer deps (prod only). Must precede "assets": app.css imports Flux CSS
# from vendor/. Flux Pro auth: auth.json lives only on the VPS, baked into the
# local composer-auth:local image (built from /root/composer-auth) — never in
# git, deleted from this layer after install.
FROM base AS dependencies
COPY composer.json composer.lock ./
COPY --from=composer-auth:local /auth.json ./auth.json
USER root
RUN chown www-data:www-data auth.json
USER www-data
RUN composer install --no-interaction --prefer-dist --no-scripts --no-dev --optimize-autoloader && rm -f auth.json

# JS assets (vendor/ copied in: app.css @source-scans Flux stubs)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=dependencies /var/www/html/vendor ./vendor
RUN npm ci && npm run build

# Runtime (WORKDIR /var/www/html inherited from base)
FROM base
COPY --chown=www-data:www-data . /var/www/html
COPY --from=dependencies --chown=www-data:www-data /var/www/html/vendor /var/www/html/vendor
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build
USER root
RUN rm /usr/bin/composer && mkdir /storage && chown www-data:www-data /storage
USER www-data
