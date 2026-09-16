FROM composer:2.8 AS dependencies

WORKDIR /app
COPY composer.json ./
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-progress

FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libxml2-dev \
    && docker-php-ext-install dom pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY --from=dependencies /app/vendor ./vendor
COPY . .

EXPOSE 8080

CMD ["sh", "-c", "php bin/migrate.php && php -S 0.0.0.0:8080 -t public public/index.php"]
