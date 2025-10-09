# Stage 1: Build Vue frontend
FROM node:20 AS frontend_builder

WORKDIR /app

COPY vms-frontend/vue-project/package*.json ./
RUN npm ci

COPY vms-frontend/vue-project/ ./
RUN npm run build


# Stage 2: Build Laravel backend with PHP-FPM and Nginx
FROM php:8.3-fpm-alpine AS final

RUN apk add --no-cache \
    nginx supervisor libzip-dev libpng-dev libxml2-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql opcache zip gd bcmath \
    && apk del $PHPIZE_DEPS

RUN mkdir -p /var/log/nginx /var/lib/nginx/tmp /var/run

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY backend/ ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY .render/nginx.conf /etc/nginx/http.d/default.conf
COPY .render/supervisord.conf /etc/supervisord.conf

COPY --from=frontend_builder /app/dist/ /var/www/html/public/build/

RUN chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

RUN nginx -t

EXPOSE 8000

CMD ["/bin/sh", "-c", "supervisord -c /etc/supervisord.conf"]
