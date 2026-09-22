#!/bin/sh
# Startup sequence for the app container (docs/design.md §3.2).
# Nothing here is destructive: migrate only applies pending migrations.
set -e

cd /var/www/html

# On a Linux host the bind mount keeps the files' real owner (for example
# uid 1000). PHP-FPM's www-data (uid 33) could not write storage/, and files
# created here as root would belong to root on the host. So www-data takes the
# owner's uid and gid, and every step below runs as www-data. Docker Desktop
# for macOS shows the mount as owned by root and lets any user write, so there
# www-data keeps uid 33.
owner_uid=$(stat -c %u .)
owner_gid=$(stat -c %g .)
if [ "$owner_uid" != 0 ] && [ "$(id -u www-data)" != "$owner_uid" ]; then
    groupmod --non-unique --gid "$owner_gid" www-data
    usermod --non-unique --uid "$owner_uid" --gid "$owner_gid" www-data
fi

as_www_data() {
    setpriv --reuid=www-data --regid=www-data --init-groups "$@"
}

if [ ! -d vendor ]; then
    echo "[entrypoint] vendor/ is missing, running composer install..."
    as_www_data composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    echo "[entrypoint] .env is missing, creating it from .env.example..."
    as_www_data cp .env.example .env
    as_www_data php artisan key:generate --force
fi

echo "[entrypoint] applying pending migrations..."
as_www_data php artisan migrate --force

# Seeds only when the companies table is empty, so restarts keep your data.
echo "[entrypoint] seeding demo data if the database is empty..."
as_www_data php artisan app:seed-demo-if-empty

echo "[entrypoint] ready"
touch /tmp/app-ready

exec "$@"
