FROM richarvey/nginx-php-fpm:3.1.6

# Establecer el directorio de trabajo
WORKDIR /var/www/html

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

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Configurar permisos requeridos por Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
