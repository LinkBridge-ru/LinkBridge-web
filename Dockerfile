FROM php:8.4-fpm-alpine AS builder

# Install Build Requirements
RUN apk add --no-cache ${PHPIZE_DEPS} \
    postgresql-dev postgresql-libs sqlite-libs sqlite-dev \
    bash linux-headers curl-dev oniguruma-dev yaml-dev icu-dev

# Build Php Plugins
RUN docker-php-ext-install -j"$(nproc)" \
    curl mbstring pdo pdo_mysql pdo_pgsql pdo_sqlite mysqli pgsql opcache intl

# Build Php Plugins from PECL
RUN pecl install apcu yaml && docker-php-ext-enable apcu yaml

# Download Symfony
RUN wget https://get.symfony.com/cli/installer -O - | bash

FROM php:8.4-fpm-alpine

RUN apk add --no-cache bash git curl figlet postgresql-libs sqlite-libs yaml icu

COPY --from=builder /usr/local/etc/php /usr/local/etc/php
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /root/.symfony5/bin/symfony /usr/local/bin/symfony
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

USER www-data

WORKDIR /var/www/linkbridge_app

COPY --chown=www-data:www-data . /var/www/linkbridge_app

ENV APP_ENV=dev \
	DATABASE_URL="sqlite:///%kernel.project_dir%/var/linkbridge.db" \
	TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16 \
	THIS_PROJECT_NAME="LinkBridge" \
	THIS_PROJECT_QR_VENDOR="https://api.qrserver.com/v1/create-qr-code/?margin=20&size=300x300&data="

RUN composer install

RUN php bin/console make:migration && \
    php bin/console doctrine:migrations:migrate -n

ENV APP_ENV=prod

EXPOSE 8000/tcp

CMD ["symfony", "serve", "--allow-cors", "--allow-http", "--no-tls", "--allow-all-ip"]
