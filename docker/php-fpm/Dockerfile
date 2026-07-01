FROM composer:2 AS composer

FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install intl opcache pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN groupmod -o -g ${WWWGROUP} www-data \
    && usermod -o -u ${WWWUSER} -g www-data www-data

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php/conf.d/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php-fpm/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php-fpm/entrypoint.sh /usr/local/bin/prod-preview-entrypoint
RUN chmod +x /usr/local/bin/prod-preview-entrypoint

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["prod-preview-entrypoint"]
CMD ["php-fpm"]
