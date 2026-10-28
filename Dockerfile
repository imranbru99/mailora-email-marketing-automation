FROM docker.io/library/node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM docker.io/library/php:8.3-fpm-bookworm AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip libcurl4-openssl-dev libfreetype6-dev \
        libicu-dev libjpeg62-turbo-dev libonig-dev libpng-dev libxml2-dev \
        libzip-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath curl gd intl mbstring opcache \
        pcntl pdo_mysql pdo_sqlite sockets xml zip \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

FROM php-base AS app
LABEL org.opencontainers.image.title="Mailora" \
      org.opencontainers.image.authors="Imran Ahmed <me@imrandev.bd>" \
      org.opencontainers.image.url="https://imrandev.bd"
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
COPY --from=assets /app/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/mailora.ini
COPY docker/entrypoint.sh /usr/local/bin/mailora-entrypoint
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
    && composer check-platform-reqs --no-dev \
    && php artisan package:discover --ansi \
    && php artisan filament:assets \
    && ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/mailora-entrypoint
USER www-data
ENTRYPOINT ["mailora-entrypoint"]
CMD ["php-fpm", "-F"]

FROM docker.io/nginxinc/nginx-unprivileged:1.28-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
EXPOSE 8080
