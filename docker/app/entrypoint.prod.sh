#!/bin/sh
# Startup of the production app container (docs/deployment.md). The code and
# vendor/ are already in the image; migrations run in the deploy script, never
# here, and the demo seeder never runs in production.
set -e

cd /var/www/html

# Cache config, events, routes and views (Laravel "Deployment → Optimization").
# It runs at start, not at build time, because the config comes from the .env
# that is mounted from the server.
php artisan optimize

exec "$@"
