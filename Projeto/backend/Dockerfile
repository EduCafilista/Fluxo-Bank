# Build do frontend documentado: Blade + Tailwind CSS via Vite.
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* tailwind.config.js postcss.config.js vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm install --no-audit --no-fund && npm run build

# Aplicação Laravel / API REST preservada.
FROM php:8.4-cli
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip git \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
COPY --from=frontend /app/public/build /app/public/build
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
# As migrations continuam no startup. Não cachear configuração no build: envvars do Render chegam no runtime.
CMD ["sh", "-c", "php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
