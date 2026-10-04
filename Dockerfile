FROM richarvey/nginx-php-fpm:3.1.6

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Copiar todos los archivos del proyecto al contenedor
COPY . /var/www/html

# Variables de entorno para la configuración del servidor
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV COMPOSER_ALLOW_SUPERUSER 1

# Instalar dependencias de Composer en la construcción
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs
