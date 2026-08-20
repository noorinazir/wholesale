#!/bin/bash
set -e

# Ensure storage directories exist and are writable
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chmod -R 775 storage bootstrap/cache

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Create SQLite database in /var/data (persistent disk on Render)
touch /var/data/database.sqlite
chmod 666 /var/data/database.sqlite

# Generate APP_KEY if not set in environment
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
else
    # Ensure APP_KEY is written to .env
    sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" .env
fi

# Write other env vars to .env so Laravel picks them up
for var in APP_ENV APP_DEBUG APP_URL DB_CONNECTION DB_DATABASE SESSION_DRIVER QUEUE_CONNECTION CACHE_STORE LOG_CHANNEL MAIL_MAILER KIMI_API_KEY KIMI_MODEL KIMI_BASE_URL; do
    if [ -n "${!var}" ]; then
        if grep -q "^${var}=" .env; then
            sed -i "s|^${var}=.*|${var}=${!var}|" .env
        else
            echo "${var}=${!var}" >> .env
        fi
    fi
done

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
