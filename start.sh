#!/bin/bash
set -e

echo "==> Waiting for database connection..."
sleep 10

echo "==> Running database migrations..."
php artisan migrate --force

echo "==> Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "Seeding..."
php artisan db:seed --force  || echo "Admin user already exists"

echo "==> Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting server on port ${PORT:-8080}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
