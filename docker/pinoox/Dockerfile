# syntax=docker/dockerfile:1
#
# Pinoox framework runtime image (infrastructure only).
# Provides PHP, Apache, extensions, Node.js, and Composer.
# Does not bundle application code — mount pincore, apps, and project files at runtime.

ARG NODE_VERSION=22.12.0

FROM node:${NODE_VERSION}-bookworm-slim AS node

FROM php:8.2-apache-bookworm

LABEL org.opencontainers.image.title="Pinoox" \
      org.opencontainers.image.description="Runtime image for the Pinoox PHP framework (core infrastructure only)" \
      org.opencontainers.image.source="https://github.com/pinoox/pinoox" \
      org.opencontainers.image.licenses="MIT"

ARG NODE_VERSION=22.12.0

COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/npm /usr/local/bin/npm
COPY --from=node /usr/local/bin/npx /usr/local/bin/npx

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        mysqli \
        pdo_mysql \
        zip \
        gd \
        intl \
        mbstring \
        opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/pinoox/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1
