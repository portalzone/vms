#!/bin/sh
set -e

echo "Waiting for database..."
sleep 10

echo "Running migrations..."
php artisan migrate --force

echo "Seeding..."
php artisan db:seed --force

echo "Starting supervisord"
supervisord -c /etc/supervisord.conf
