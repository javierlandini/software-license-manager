FROM php:8.5-fpm-alpine

ARG UID=1000
ARG GID=1000

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apk add --no-cache git unzip shadow \
    && install-php-extensions bcmath intl pcntl pdo_mysql redis zip \
    && usermod -o -u "${UID}" www-data \
    && groupmod -o -g "${GID}" www-data

COPY php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
