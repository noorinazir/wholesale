#!/bin/bash
set -e

# Ensure storage directories exist and are writable
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chmod -R 775 storage bootstrap/cache

# Create SQLite database in /tmp (ephemeral disk on Cloudflare Containers)
touch /tmp/database.sqlite
chmod 666 /tmp/database.sqlite

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Run base seeder (creates admin user + system settings)
php artisan db:seed --class=DatabaseSeeder --force

# Cache config and routes for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Apache in foreground
apache2-foreground
