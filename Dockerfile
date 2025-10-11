# Stage 1: Build Vue frontend
FROM node:22-alpine AS frontend-builder

WORKDIR /frontend
COPY vms-frontend/vue-project/package*.json ./
RUN npm install
COPY vms-frontend/vue-project/ .
RUN npm run build

# Stage 2: Build PHP backend
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy backend files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy built frontend to Laravel public directory
COPY --from=frontend-builder /frontend/dist ./public

# CRITICAL: Create index.php fallback for SPA
RUN echo '<?php' > public/index.php && \
    echo 'define("LARAVEL_START", microtime(true));' >> public/index.php && \
    echo 'if (file_exists($maintenance = __DIR__."/../storage/framework/maintenance.php")) {' >> public/index.php && \
    echo '    require $maintenance;' >> public/index.php && \
    echo '}' >> public/index.php && \
    echo 'require __DIR__."/../vendor/autoload.php";' >> public/index.php && \
    echo '$app = require_once __DIR__."/../bootstrap/app.php";' >> public/index.php && \
    echo '$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);' >> public/index.php && \
    echo '$response = $kernel->handle(' >> public/index.php && \
    echo '    $request = Illuminate\Http\Request::capture()' >> public/index.php && \
    echo ');' >> public/index.php && \
    echo '$response->send();' >> public/index.php && \
    echo '$kernel->terminate($request, $response);' >> public/index.php

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copy nginx config
COPY .render/nginx.conf /etc/nginx/http.d/default.conf

# Copy supervisor config
COPY .render/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create start script
RUN echo '#!/bin/sh' > /start.sh && \
    echo 'set -e' >> /start.sh && \
    echo 'echo "==> Waiting for database..."' >> /start.sh && \
    echo 'sleep 10' >> /start.sh && \
    echo 'echo "==> Running migrations..."' >> /start.sh && \
    echo 'php artisan migrate --force' >> /start.sh && \
    echo 'echo "==> Seeding database..."' >> /start.sh && \
    echo 'php artisan db:seed --force || echo "Seeders already run"' >> /start.sh && \
    echo 'echo "==> Clearing caches..."' >> /start.sh && \
    echo 'php artisan config:clear' >> /start.sh && \
    echo 'php artisan cache:clear' >> /start.sh && \
    echo 'php artisan route:clear' >> /start.sh && \
    echo 'php artisan view:clear' >> /start.sh && \
    echo 'echo "==> Optimizing..."' >> /start.sh && \
    echo 'php artisan config:cache' >> /start.sh && \
    echo 'php artisan route:cache' >> /start.sh && \
    echo 'php artisan view:cache' >> /start.sh && \
    echo 'echo "==> Starting services..."' >> /start.sh && \
    echo 'exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /start.sh && \
    chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
