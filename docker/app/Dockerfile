# syntax=docker/dockerfile:1

# One Dockerfile, several targets (docs/design.md §3.1, docs/deployment.md):
#   dev  the local development image (compose.yaml)
#   app  production PHP-FPM with the code, vendor/ and the built assets
#   web  production nginx with public/
# Without --target Docker builds the last stage, so compose.yaml names dev.

# PHP 8.4 with FPM, on Debian trixie. Laravel 13 supports PHP 8.3-8.5;
# docs/design.md pins 8.4.
FROM php:8.4-fpm-trixie AS base

# Libraries for the PHP extensions we need:
#   libicu-dev  -> intl
#   libpq-dev   -> pdo_pgsql
#   libzip-dev  -> zip (Composer uses it to unpack packages quickly)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM base AS dev

# Composer needs git and unzip for VCS and archive handling.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Composer 2, lifted out of the official Composer image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node 24 LTS, lifted out of the official Node image. The trixie variant is
# used so node links against the same glibc as this image.
COPY --from=node:24-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-trixie-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Composer resolves large dependency graphs; don't let PHP's CLI memory limit
# abort the install.
ENV COMPOSER_MEMORY_LIMIT=-1

# This image ships no php.ini, so PHP's compiled-in 128M default applies.
# Larastan at level 7 exhausts it and its parallel workers crash. The conf.d
# drop-in applies to both the CLI and FPM.
RUN printf 'memory_limit=512M\n' > /usr/local/etc/php/conf.d/zz-memory-limit.ini

# Composer and npm keep their caches here instead of in $HOME, so they work for
# every user: root, www-data and the checkout's owner (see entrypoint.sh).
ENV COMPOSER_HOME=/tmp/composer \
    npm_config_cache=/tmp/npm-cache

COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm", "--nodaemonize"]
