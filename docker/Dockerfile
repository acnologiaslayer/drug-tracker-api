FROM php:8.3-fpm-alpine

# Install system dependencies (Alpine uses apk, smaller packages)
RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    mysql-client \
    && rm -rf /var/cache/apk/*

# Install PHP extensions one at a time to reduce peak memory usage
# This is critical for t3.micro instances with only 1GB RAM
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install zip
RUN docker-php-ext-install opcache

# Install intl separately (this is the most memory-intensive)
RUN docker-php-ext-install intl

# Install redis from PECL
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

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

# Set permissions (Alpine doesn't have usermod, use adduser)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
