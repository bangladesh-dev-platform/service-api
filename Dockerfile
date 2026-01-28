FROM php:8.4-fpm-alpine

# Install required system packages and PHP extensions
RUN apk add --no-cache \
        bash \
        git \
        curl \
        unzip \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        icu-dev \
        zlib-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        intl \
        opcache

# Copy Composer from the Composer image (official recommendation)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency manifests and install dependencies
COPY composer.* ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Copy the rest of the application
COPY . .

# Expose FPM port
EXPOSE 9000

CMD ["php-fpm"]
