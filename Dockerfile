# ============================================================
# Dockerfile untuk Laravel + Inertia.js + Vue (Monolith)
# ============================================================
# Multi-stage build:
#   Stage 1 (frontend): Node → build Vue assets (vite build)
#   Stage 2 (backend):  PHP-FPM → composer install + copy assets
# ============================================================

# --- Stage 1: Build Vue assets ---
FROM node:20-alpine AS frontend

WORKDIR /var/www/html

# Copy lock files dulu (untuk caching layer)
COPY package.json package-lock.json ./

# Install dependencies (ci = clean install dari lock file)
RUN npm ci

# Copy source code
COPY . .

# Build Vue → output ke public/build/
RUN npm run build

# --- Stage 2: PHP-FPM (final image) ---
FROM php:8.2-fpm-alpine AS backend

WORKDIR /var/www/html

# Install system dependencies + PHP extensions
# - libpng-dev, libzip-dev: untuk extension gd & zip
# - mysql-client: untuk artisan migrate dll
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    mysql-client \
    && docker-php-ext-install \
    pdo_mysql \
    gd \
    zip \
    bcmath \
    opcache

# Install Composer (copy binary dari image composer official)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files dulu (untuk caching layer)
COPY composer.json composer.lock ./

# Install PHP dependencies (no-dev = tanpa package testing)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# Copy application code
COPY . .

# Copy built Vue assets dari stage 1
COPY --from=frontend /var/www/html/public/build /var/www/html/public/build

# Set permissions untuk storage & bootstrap/cache
# (Laravel butuh write access ke folder ini)
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Expose port PHP-FPM
EXPOSE 9000

# Startup script: jalankan migration lalu start PHP-FPM
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["/usr/local/bin/entrypoint.sh"]
