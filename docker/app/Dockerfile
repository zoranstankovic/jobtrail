# syntax=docker/dockerfile:1

# PHP 8.4 with FPM, on Debian trixie. Laravel 13 supports PHP 8.3-8.5;
# docs/design.md pins 8.4.
FROM php:8.4-fpm-trixie

# Build and runtime dependencies for the PHP extensions we need:
#   libicu-dev  -> intl
#   libpq-dev   -> pdo_pgsql
#   libzip-dev  -> zip (Composer uses it to unpack packages quickly)
#   git, unzip  -> Composer needs them for VCS and archive handling
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql zip \
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

WORKDIR /var/www/html
