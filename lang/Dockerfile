FROM richarvey/nginx-php-fpm:3.1.6

# Copiar todos los archivos del proyecto al contenedor
COPY . /var/www/html

# Indicar que la carpeta pública de Laravel es la raíz web
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Permitir el uso de Composer como superusuario
ENV COMPOSER_ALLOW_SUPERUSER 1
