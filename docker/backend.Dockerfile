FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

RUN mkdir -p /var/lib/php/sessions && chmod 733 /var/lib/php/sessions
RUN printf "session.save_path=/var/lib/php/sessions\n" > /usr/local/etc/php/conf.d/sessions.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app