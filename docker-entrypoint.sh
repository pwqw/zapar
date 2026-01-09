#!/bin/sh
set -e


# Instalar dependencias de Composer (requerido antes de composer koel:init)
if [ ! -d /var/www/html/vendor ]; then
    echo '📦 Instalando dependencias de Composer...'
    composer install --no-interaction --prefer-dist
fi

# Instalar dependencias de Node.js (necesarias para desarrollo)
if [ ! -d /var/www/html/node_modules ]; then
    echo '📦 Instalando dependencias de Node.js...'
    pnpm install
fi

# Crear estructura de directorios necesaria
mkdir -p /var/www/html/database
mkdir -p /var/www/html/media
chown -R www-data:www-data /var/www/html/media
chmod -R 775 /var/www/html/media

# Crear archivo de base de datos SQLite si no existe (necesario antes de koel:init)
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Crear .env desde .env.example si no existe (koel:init lo hace, pero lo pre-configuramos para modo no-interactivo)
if [ ! -f /var/www/html/.env ]; then
    echo '📝 Creando archivo .env desde .env.example...'
    cp /var/www/html/.env.example /var/www/html/.env
    
    # Configurar para SQLite en Docker (requerido para modo no-interactivo)
    # Según la documentación oficial, DB_CONNECTION debe estar presente para que composer koel:init funcione
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite-persistent/' /var/www/html/.env
    sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/html/database/database.sqlite|' /var/www/html/.env
    
    # Configuraciones recomendadas para Docker/desarrollo
    sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' /var/www/html/.env
    sed -i 's/^CACHE_DRIVER=.*/CACHE_DRIVER=file/' /var/www/html/.env
    sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' /var/www/html/.env
    sed -i 's/^MAIL_MAILER=.*/MAIL_MAILER=log/' /var/www/html/.env
    
    # Configurar MEDIA_PATH para Docker
    if ! grep -q "^MEDIA_PATH=" /var/www/html/.env || grep -q "^MEDIA_PATH=$" /var/www/html/.env; then
        if grep -q "^MEDIA_PATH=" /var/www/html/.env; then
            sed -i 's|^MEDIA_PATH=.*|MEDIA_PATH=/var/www/html/media|' /var/www/html/.env
        else
            echo "MEDIA_PATH=/var/www/html/media" >> /var/www/html/.env
        fi
    fi
fi

# Ejecutar koel:init siguiendo la documentación oficial
# La documentación oficial recomienda usar "composer koel:init" en lugar de "php artisan koel:init"
# Usamos --no-assets porque estamos en modo desarrollo y usamos Vite
# Usamos --no-scheduler porque no es necesario en Docker para desarrollo
echo '🚀 Inicializando Koel usando el proceso oficial (composer koel:init)...'
composer koel:init -- --no-assets --no-interaction --no-scheduler

# Iniciar servidor de desarrollo con hot reload
echo '✅ Iniciando servidor de desarrollo...'
# Ejecutar servidor Laravel en 0.0.0.0 para que sea accesible desde el host
# Vite está configurado en vite.config.js para escuchar en 0.0.0.0 con HMR en localhost
# Solo ejecutar queue:listen si QUEUE_CONNECTION no es 'sync' (no necesario para sync)
# Verificar la configuración de cola antes de iniciar
QUEUE_CONN=$(grep "^QUEUE_CONNECTION=" /var/www/html/.env 2>/dev/null | cut -d'=' -f2 || echo "sync")
if [ "$QUEUE_CONN" = "sync" ]; then
  echo 'ℹ️  Queue connection es "sync", omitiendo queue:listen para reducir consumo de CPU'
  exec npx concurrently -k -c "#93c5fd,#fdba74" \
    "php artisan serve --host=0.0.0.0 --port=8000" \
    "vite" \
    --names=server,vite \
    --restart-tries=3
else
  echo 'ℹ️  Queue connection es "'"$QUEUE_CONN"'", iniciando queue:listen'
  exec npx concurrently -k -c "#93c5fd,#c4b5fd,#fdba74" \
    "php artisan serve --host=0.0.0.0 --port=8000" \
    "php artisan queue:listen --tries=1 --sleep=3 --max-time=3600" \
    "vite" \
    --names=server,queue,vite \
    --restart-tries=3
fi

