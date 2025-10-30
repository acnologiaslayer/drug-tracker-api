FROM php:8.3-fpm

# Use the PHP extension installer for MUCH faster builds
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install PHP extensions (uses pre-compiled binaries when possible - 10x faster!)
RUN install-php-extensions \
    redis \
    intl \
    pdo_mysql \
    zip \
    opcache

# Install minimal system dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application files
COPY . .

# Add tuned PHP-FPM pool config for t3.micro low-memory instances
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-dt-www.conf

# Add opcache config tuned for t3.micro
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

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
