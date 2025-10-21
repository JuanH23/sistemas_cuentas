FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git curl libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
    zip unzip oniguruma-dev icu-dev libxml2-dev \
    mysql-client supervisor nano vim \
    autoconf g++ make

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN apk del autoconf g++ make

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN addgroup -g 1000 laravel \
    && adduser -u 1000 -G laravel -s /bin/sh -D laravel

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]