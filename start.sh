#!/bin/sh
set -e

echo "Waiting for database..."
sleep 10

echo "Running migrations..."
php artisan migrate --force

echo "Seeding database..."
php artisan db:seed --force || echo "Seeders already run"

echo "Starting services..."
supervisord -c /etc/supervisord.conf
