# --- ETAPA 1: Compilación de Assets con Node 20 ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# --- ETAPA 2: Servidor PHP/Nginx para Producción ---
FROM richarvey/nginx-php-fpm:3.1.6

WORKDIR /var/www/html

# Copiar el código de la aplicación
COPY . /var/www/html

# Copiar la carpeta compilada de Vite desde la etapa anterior
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Variables de entorno
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV LOG_CHANNEL stderr
ENV ENABLE_PRESTISSIMO 0
ENV PORT 80

# Sobrescribir las configuraciones por defecto de Nginx con la regla de reescritura de Laravel
COPY nginx.conf /etc/nginx/sites-available/default.conf
COPY nginx.conf /etc/nginx/sites-enabled/default.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Instalar dependencias de PHP
RUN composer update --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Comando de inicio
ENTRYPOINT ["sh", "-c", "php artisan config:clear && php artisan migrate --force && /start.sh"]
