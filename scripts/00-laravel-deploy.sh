#!/usr/bin/env bash

# Exit immediately if any command fails
set -e

# --- 1. Frontend Build ---
echo "Installing Node dependencies..."
# NOTE: Navigate to your Vue/Vite project directory if it's separate, 
# but assuming a monolithic structure, running from the root is often fine 
# if package.json is in the root, or adjust the path:
# cd vms-frontend/vue-project 
npm install 
echo "Building Vue assets with Vite..."
npm run build 
# cd .. # Go back to root if you navigated away

# --- 2. Backend Setup ---
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# --- 3. Laravel Configuration and Optimization ---
# Generate Application Key (only if APP_KEY isn't set via Render ENV variables)
# php artisan key:generate --show

echo "Caching config, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- 4. Database Migrations ---
echo "Running database migrations..."
# The --force flag is required for migrations in a production environment
php artisan migrate --force

echo "Deployment script finished successfully!"
