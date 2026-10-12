# syntax=docker/dockerfile:1

# --- ETAPA 1: Compilación de Assets con Node 22 (LTS con soporte) ---
FROM node:22-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
# `npm ci` instala exactamente lo que fija package-lock.json (reproducible).
RUN npm ci
COPY . .
RUN npm run build

# --- ETAPA 2: PHP 8.4 + Nginx (producción) ---
# Imagen oficial mantenida. La anterior (richarvey/nginx-php-fpm:3.1.6) no
# recibe actualizaciones desde 2023-06 y va con PHP 8.2.7, que además es
# incompatible con las dependencias (Symfony 8.1 exige PHP >= 8.4.1).
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# Extensiones de PHP (Laravel + dompdf + MySQL/SQLite) y servicios del contenedor.
# `sqlite-dev` aporta `sqlite3.pc`, que exige `pkg-config` al compilar
# `pdo_sqlite`; sin él el build falla con
# «Package requirements (sqlite3 >= 3.7.7) were not met: Package 'sqlite3' not found».
RUN apk add --no-cache \
        nginx supervisor icu-dev oniguruma-dev libzip-dev sqlite-dev \
        libpng-dev freetype-dev libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql pdo_sqlite mbstring bcmath gd zip intl opcache \
    && rm -rf /var/cache/apk/*

# Composer para instalar dependencias en el build.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Copiar el código de la aplicación.
COPY . /var/www/html

# Copiar la carpeta compilada de Vite desde la etapa anterior.
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Dependencias de PHP desde el lock: reproducible y sí valida la versión de PHP.
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configuración de nginx, PHP y supervisor.
COPY nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Directorios de escritura y caché de vistas (config se cachea en runtime).
RUN mkdir -p storage/framework/sessions storage/framework/views \
             storage/framework/cache/data storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan view:cache || true

# Entorno de producción por defecto; se sobrescribe desde el host.
# IMPORTANTE: APP_KEY, DB_* y ADMIN_MASTER_KEY deben definirse en runtime
# (el .env NO se copia a la imagen; ver .dockerignore).
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget -qO- http://127.0.0.1/up || exit 1

ENTRYPOINT ["sh", "-c", "php artisan config:cache && php artisan storage:link || true && php artisan migrate --force && exec supervisord -c /etc/supervisord.conf"]
