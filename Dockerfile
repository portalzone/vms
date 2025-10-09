# Stage 1: Build Vue frontend
FROM node:22 AS frontend_builder

WORKDIR /app

COPY vms-frontend/vue-project/package*.json ./
RUN npm ci

COPY vms-frontend/vue-project/ ./
RUN npm run build


# Stage 2: Build Laravel backend with PHP-FPM and Nginx
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    supervisor \
    nginx \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip bcmath \
    && apk del $PHPIZE_DEPS


RUN mkdir -p /var/log/nginx /var/lib/nginx/tmp /var/run

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Since Laravel backend is in root, copy everything from root
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY .render/nginx.conf /etc/nginx/http.d/default.conf
COPY .render/supervisord.conf /etc/supervisord.conf

COPY --from=frontend_builder /app/dist/ /var/www/html/public/build/

RUN chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

RUN nginx -t

EXPOSE 8000

CMD ["/bin/sh", "-c", "supervisord -c /etc/supervisord.conf"]
