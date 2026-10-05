FROM richarvey/nginx-php-fpm:3.1.6

WORKDIR /var/www/html

COPY . /var/www/html

# Variables de entorno
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV LOG_CHANNEL stderr
ENV ENABLE_PRESTISSIMO 0
ENV PORT 80

# Forzar actualización de dependencias compatibles con PHP 8.2 omitiendo bloqueos de plataforma
RUN composer update --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Comando de inicio: Limpia caché, ejecuta migraciones en Clever Cloud y arranca el servidor
ENTRYPOINT ["sh", "-c", "php artisan config:clear && php artisan migrate --force && /start.sh"]
