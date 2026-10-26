FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --optimize-autoloader

FROM php:8.4-fpm-alpine
RUN apk add --no-cache icu-libs libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql intl bcmath opcache \
    && apk del .build-deps
RUN printf 'upload_max_filesize=10M\npost_max_size=40M\nmax_file_uploads=20\n' > /usr/local/etc/php/conf.d/uploads.ini
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private \
    && chown -R www-data:www-data bootstrap/cache storage \
    && chmod -R ug+rwX bootstrap/cache storage
EXPOSE 9000
CMD ["php-fpm"]
