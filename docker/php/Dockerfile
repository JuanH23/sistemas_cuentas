FROM php:8.4.2-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    git curl libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
    zip unzip oniguruma-dev postgresql-dev icu-dev libxml2-dev libxslt-dev \
    supervisor pcre-dev $PHPIZE_DEPS

# Extensiones PHP para Laravel/Filament
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip intl xml soap xsl opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# (Opcional) usuario de trabajo para shell/Composer
RUN addgroup -g 1000 laravel \
 && adduser -u 1000 -G laravel -s /bin/sh -D laravel

# Alinear permisos con FPM (pool www-data)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
