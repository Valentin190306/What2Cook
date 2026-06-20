FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev dcron \
    && docker-php-ext-install pdo pdo_pgsql

RUN mkdir -p /var/lib/php/sessions

COPY docker/crontab /etc/crontabs/root
RUN printf "session.save_path=/var/lib/php/sessions\n" > /usr/local/etc/php/conf.d/sessions.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint \
    && sed -i 's/\r$//' /usr/local/bin/docker-entrypoint

ENTRYPOINT ["sh", "/usr/local/bin/docker-entrypoint"]
CMD ["php-fpm"]

WORKDIR /var/www/app