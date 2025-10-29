FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update \
    && apt-get install -y \
        curl \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libxml2-dev \
        libonig-dev \
        build-essential \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install \
        intl \
        pdo_mysql \
        zip \
    && apt-get purge -y --auto-remove build-essential \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configure PHP-FPM to run as www-data
RUN usermod -u 1000 www-data \
    && groupmod -g 1000 www-data

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
