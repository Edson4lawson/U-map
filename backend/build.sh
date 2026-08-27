#!/bin/bash

# Build script for Render deployment

set -e

echo "=== U-Map Backend Build for Render ==="

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader --prefer-dist

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate
fi

# Clear and cache config
echo "Optimizing configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database places
echo "Seeding campus places..."
php artisan db:seed --class="Database\Seeders\PlaceSeeder" --force

# Link storage
echo "Linking storage..."
php artisan storage:link

# Set permissions
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo "=== Build completed successfully ==="
