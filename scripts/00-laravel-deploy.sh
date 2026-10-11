#!/usr/bin/env bash

echo "Instalando dependencias con Composer..."
composer install --no-dev --working-dir=/var/www/html

echo "Asegurando permisos de almacenamiento..."
# Antes era `chmod -R 777`: world-writable, cualquier proceso podía escribir.
# El usuario del servidor web (www-data) es el dueño; 775 es suficiente.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Ejecutando migraciones de la base de datos..."
php artisan migrate --force

echo "Optimizando Laravel..."
php artisan config:cache
# NO se usa route:cache: hay rutas con closures (/, /dashboard y
# /usuarios/{user}/rol) que no se pueden serializar y harían fallar el comando.
php artisan view:cache
