#!/bin/sh
# Startup sequence for the app container (docs/design.md §3.2).
# Nothing here is destructive: migrate only applies pending migrations.
set -e

cd /var/www/html

if [ ! -d vendor ]; then
    echo "[entrypoint] vendor/ is missing, running composer install..."
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    echo "[entrypoint] .env is missing, creating it from .env.example..."
    cp .env.example .env
    php artisan key:generate --force
fi

echo "[entrypoint] applying pending migrations..."
php artisan migrate --force

# Seeds only when the companies table is empty, so restarts keep your data.
echo "[entrypoint] seeding demo data if the database is empty..."
php artisan app:seed-demo-if-empty

echo "[entrypoint] ready"
touch /tmp/app-ready

exec "$@"
