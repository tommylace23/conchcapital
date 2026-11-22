FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libpng-dev libicu-dev libonig-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring zip gd bcmath intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Laravel application
COPY test/backend/ ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Frontend pages served from public/
COPY test/*.html ./public/
COPY test/js ./public/js
COPY test/css ./public/css
COPY test/assets ./public/assets

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["/usr/local/bin/start.sh"]
