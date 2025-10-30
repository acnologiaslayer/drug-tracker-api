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

# Run migrations
echo "Running database migrations..."
php artisan migrate --force --no-interaction 2>&1 | tee /tmp/migrate.log || {
    if grep -q "already exists" /tmp/migrate.log; then
        echo ""
        echo "⚠️  Tables already exist - this is normal on container restart."
        echo "Migration will be skipped. Database is ready."
        echo ""
    else
        echo ""
        echo "❌ Migration failed with unexpected error:"
        cat /tmp/migrate.log
        echo ""
        exit 1
    fi
}

# Optimize for production
echo "Optimizing application..."
php artisan optimize

echo "Application ready!"

# Execute the main command
exec "$@"
