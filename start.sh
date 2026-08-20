#!/bin/bash
set -e

# Ensure storage directories exist and are writable by Apache
mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Create SQLite database in /var/data (persistent disk on Render)
# SQLite needs write access to both the database file AND the directory (for journal/WAL files)
mkdir -p /var/data
chown www-data:www-data /var/data
chmod 775 /var/data
touch /var/data/database.sqlite
chown www-data:www-data /var/data/database.sqlite
chmod 664 /var/data/database.sqlite

# Override critical config in .env for Render deployment
sed -i 's|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|' .env
sed -i 's|^DB_HOST=.*|DB_HOST=|' .env
sed -i 's|^DB_PORT=.*|DB_PORT=|' .env
sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/data/database.sqlite|' .env
sed -i 's|^DB_USERNAME=.*|DB_USERNAME=|' .env
sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=|' .env
sed -i 's|^APP_ENV=.*|APP_ENV=production|' .env
sed -i 's|^APP_DEBUG=.*|APP_DEBUG=false|' .env
sed -i 's|^SESSION_DRIVER=.*|SESSION_DRIVER=file|' .env
sed -i 's|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|' .env
sed -i 's|^CACHE_STORE=.*|CACHE_STORE=file|' .env
sed -i 's|^LOG_CHANNEL=.*|LOG_CHANNEL=stderr|' .env
sed -i 's|^MAIL_MAILER=.*|MAIL_MAILER=log|' .env

# Force HTTPS for asset URLs (Render terminates SSL at proxy)
if grep -q "^ASSET_URL=" .env; then
    sed -i "s|^ASSET_URL=.*|ASSET_URL=$APP_URL|" .env
else
    echo "ASSET_URL=$APP_URL" >> .env
fi

# Tell Laravel to trust the Render proxy headers for HTTPS
if grep -q "^TRUSTED_PROXIES=" .env; then
    sed -i 's|^TRUSTED_PROXIES=.*|TRUSTED_PROXIES=*|' .env
else
    echo "TRUSTED_PROXIES=*" >> .env
fi

# Set APP_URL if provided
if [ -n "$APP_URL" ]; then
    sed -i "s|^APP_URL=.*|APP_URL=$APP_URL|" .env
    # Also set ASSET_URL so Vite assets resolve correctly
    if grep -q "^ASSET_URL=" .env; then
        sed -i "s|^ASSET_URL=.*|ASSET_URL=$APP_URL|" .env
    else
        echo "ASSET_URL=$APP_URL" >> .env
    fi
fi

# Generate APP_KEY if not set in environment
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
else
    sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" .env
fi

# Set KIMI_API_KEY if provided
if [ -n "$KIMI_API_KEY" ]; then
    if grep -q "^KIMI_API_KEY=" .env; then
        sed -i "s|^KIMI_API_KEY=.*|KIMI_API_KEY=$KIMI_API_KEY|" .env
    else
        echo "KIMI_API_KEY=$KIMI_API_KEY" >> .env
    fi
fi

# Run migrations (continue even if some already applied)
php artisan migrate --force

# Run base seeder (creates admin user + system settings + RBAC permissions)
php artisan db:seed --class=DatabaseSeeder --force

# Clear any stale cache then re-cache for production
php artisan optimize:clear 2>/dev/null || true
php artisan config:cache
php artisan route:cache

# Start queue worker in background
php artisan queue:work --daemon --tries=3 --sleep=3 > /dev/null 2>&1 &

# Start Apache in foreground
apache2-foreground
