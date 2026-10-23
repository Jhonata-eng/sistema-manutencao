FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql bcmath intl zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/app.ini

# Roda como o mesmo UID/GID do host para que os arquivos criados no volume não fiquem com dono root.
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g ${GID} www-data

ENV COMPOSER_HOME=/tmp/composer \
    XDG_CONFIG_HOME=/tmp/config
USER www-data
WORKDIR /var/www/html
