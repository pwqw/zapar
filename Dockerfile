FROM php:8.4-fpm

# Instalar herramientas básicas (cambian menos frecuentemente)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar dependencias de desarrollo para extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    libicu-dev \
    libxslt1-dev \
    libmagickwand-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar herramientas adicionales
RUN apt-get update && apt-get install -y --no-install-recommends \
    sqlite3 \
    ffmpeg \
    procps \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP básicas
RUN docker-php-ext-install \
    pdo_sqlite \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    xsl

# Instalar extensiones PHP que requieren más dependencias
# Configurar GD con soporte para JPEG, PNG, WEBP y FreeType
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install \
    gd \
    zip

# Instalar Imagick para soporte completo de imágenes (incluyendo SVG)
# Esto hace que desarrollo sea idéntico a producción
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Configurar PHP para desarrollo (ocultar warnings de deprecación)
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/docker-php-dev.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-dev.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-dev.ini

# Configurar límites de upload según el repositorio oficial de Koel Docker
# https://github.com/koel/docker
RUN echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/docker-php-uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/docker-php-uploads.ini \
    && echo "max_execution_time = 3600" >> /usr/local/etc/php/conf.d/docker-php-uploads.ini \
    && echo "max_input_time = 3600" >> /usr/local/etc/php/conf.d/docker-php-uploads.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 18 y pnpm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g pnpm

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copiar archivos del proyecto (respetando .dockerignore)
COPY . .

# Crear estructura de directorios necesaria para Laravel/Koel
# (algunos pueden estar excluidos por .dockerignore pero los necesitamos)
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    storage/dotenv-editor \
    storage/search-indexes \
    bootstrap/cache \
    database \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Exponer puertos (Laravel y Vite)
EXPOSE 8000 5173

ENTRYPOINT ["docker-entrypoint.sh"]

