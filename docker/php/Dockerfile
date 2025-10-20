ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add --no-cache git zip unzip libpng-dev libjpeg-turbo-dev oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY backend/composer.json backend/composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist; fi

COPY backend ./
EXPOSE 9000
