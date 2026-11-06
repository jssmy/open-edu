# ==========================================
# Stage 1: Build Frontend Assets (Vite + Vue 3)
# ==========================================
FROM node:20-alpine AS frontend-builder
WORKDIR /app

# Cache node dependencies layer
COPY package.json package-lock.json* ./
RUN npm ci --prefer-offline --no-audit

# Copy frontend source code and configs
COPY tsconfig.json vite.config.ts tailwind.config.ts ./
COPY resources/ ./resources/
COPY public/ ./public/

# Compile frontend production bundles
RUN npm run build

# ==========================================
# Stage 2: Composer Dependencies Layer
# ==========================================
FROM composer:2.8 AS vendor-builder
WORKDIR /app

COPY composer.json composer.lock ./

# Install production dependencies only with autoloader optimization
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ==========================================
# Stage 3: Final Production Image (PHP 8.4-FPM + Nginx + Supervisor)
# ==========================================
FROM php:8.4-fpm-alpine AS production

LABEL maintainer="Open-Edu Team"

# Install production system dependencies, Nginx, Supervisor, and PostgreSQL driver libraries
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    curl \
    dcron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        pcntl \
    && rm -rf /var/cache/apk/*

# Production PHP settings & OPcache tuning
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && { \
    echo 'upload_max_filesize=30M'; \
    echo 'post_max_size=32M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=120'; \
    echo 'expose_php=Off'; \
} > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

# Copy application source code
COPY . .

# Copy composer dependencies from Stage 2
COPY --from=vendor-builder /app/vendor /var/www/html/vendor

# Copy compiled frontend assets from Stage 1
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Copy Nginx, Supervisor, and Entrypoint configurations
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Run package discovery and autoloader dump now that all files are in place
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative 2>/dev/null || true

# Fix permissions for runtime
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

