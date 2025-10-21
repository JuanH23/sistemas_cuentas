FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema y herramientas de compilación
RUN apk add --no-cache \
    git curl libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
    zip unzip oniguruma-dev icu-dev libxml2-dev \
    mysql-client supervisor nano vim \
    autoconf g++ make

# Instalar extensiones PHP estándar
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# ⭐ IMPORTANTE: Instalar phpredis (esto soluciona el error)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Limpiar herramientas de compilación (reducir tamaño de imagen)
RUN apk del autoconf g++ make

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Crear usuario laravel
RUN addgroup -g 1000 laravel \
    && adduser -u 1000 -G laravel -s /bin/sh -D laravel

# Ajustar permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]