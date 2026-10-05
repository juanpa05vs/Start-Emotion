FROM richarvey/nginx-php-fpm:3.1.6

WORKDIR /var/www/html

# Instalar Node.js y NPM para compilar los assets de Vite
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Copiar el proyecto
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

# Instalar dependencias de PHP y Node
RUN composer update --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs
RUN npm install && npm run build

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Comando de inicio: Limpia caché, ejecuta migraciones y arranca el servidor
ENTRYPOINT ["sh", "-c", "php artisan config:clear && php artisan migrate --force && /start.sh"]
