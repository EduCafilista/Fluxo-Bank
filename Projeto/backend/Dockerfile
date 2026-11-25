# Build do frontend documentado: Blade + Tailwind CSS via Vite.
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json tailwind.config.js postcss.config.js vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci --no-audit --no-fund && npm run build

# Aplicação Laravel / API REST preservada.
FROM alpine:3.23
RUN apk add --no-cache ca-certificates unzip \
        php84 php84-phar php84-mbstring php84-openssl php84-curl php84-dom \
        php84-tokenizer php84-xml php84-xmlwriter php84-xmlreader php84-simplexml \
        php84-pdo_pgsql php84-pgsql php84-session php84-ctype php84-fileinfo \
        php84-zip php84-iconv php84-posix php84-pcntl \
    && ln -s /usr/bin/php84 /usr/local/bin/php
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
COPY --from=frontend /app/public/build /app/public/build
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
# As migrations continuam no startup. Não cachear configuração no build: envvars do Render chegam no runtime.
CMD ["sh", "-c", "php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
