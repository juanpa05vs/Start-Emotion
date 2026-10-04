#!/usr/bin/env bash

echo "Instalando dependencias con Composer..."
composer install --no-dev --working-dir=/var/www/html

echo "Ejecutando migraciones de la base de datos..."
php artisan migrate --force

echo "Optimizando Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
