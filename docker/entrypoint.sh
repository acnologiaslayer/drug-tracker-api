#!/bin/bash

set -e

echo "Starting Drug Tracker API..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php artisan db:show > /dev/null 2>&1; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "Database is ready!"

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --no-interaction
fi

# Cache configuration
echo "Caching configuration..."
php artisan config:cache

# Run migrations (continue on error if tables already exist)
echo "Running database migrations..."
php artisan migrate --force --no-interaction || {
    echo "Migration failed, attempting to mark existing migrations as run..."
    php artisan migrate:status
    echo "Continuing with startup..."
}

# Optimize for production
echo "Optimizing application..."
php artisan optimize

echo "Application ready!"

# Execute the main command
exec "$@"
