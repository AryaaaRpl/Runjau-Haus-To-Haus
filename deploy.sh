#!/usr/bin/env bash
set -e

echo "Starting deployment..."

(php artisan down --retry=60) || true

git pull origin main

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if [ -f "package.json" ]; then
    npm ci --prefer-offline || npm install
    npm run build
fi

php artisan migrate --force
php artisan storage:link || true
php artisan optimize:clear
php artisan optimize
php artisan queue:restart || true

php artisan up

echo "Application deployed successfully!"
