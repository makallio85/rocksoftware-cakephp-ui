# Development sandbox for the rocksoftware-cakephp-ui plugin: PHP 8.4 with
# the extensions CakePHP needs and Composer. Not a deployment; see
# docker-compose.yml.
FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev \
    && docker-php-ext-install -j"$(nproc)" intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
