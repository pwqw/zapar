#!/bin/sh
set -e


# Install Composer dependencies (required before composer koel:init)
if [ ! -d /var/www/html/vendor ]; then
    echo '📦 Installing Composer dependencies...'
    composer install --no-interaction --prefer-dist
fi

# Install Node.js dependencies (required for development)
if [ ! -d /var/www/html/node_modules ]; then
    echo '📦 Installing Node.js dependencies...'
    pnpm install
fi

# Create necessary directory structure
mkdir -p /var/www/html/database
mkdir -p /var/www/html/media
chown -R www-data:www-data /var/www/html/media
chmod -R 775 /var/www/html/media

# Create SQLite database file if it doesn't exist (required before koel:init)
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Create .env from .env.example if it doesn't exist (koel:init does this, but we pre-configure it for non-interactive mode)
if [ ! -f /var/www/html/.env ]; then
    echo '📝 Creating .env from .env.example...'
    cp /var/www/html/.env.example /var/www/html/.env
    
    # Configure for SQLite in Docker (required for non-interactive mode)
    # According to official documentation, DB_CONNECTION must be present for composer koel:init to work
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite-persistent/' /var/www/html/.env
    sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/html/database/database.sqlite|' /var/www/html/.env
    
    # Recommended configurations for Docker/development
    sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' /var/www/html/.env
    sed -i 's/^CACHE_DRIVER=.*/CACHE_DRIVER=file/' /var/www/html/.env
    sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' /var/www/html/.env
    sed -i 's/^MAIL_MAILER=.*/MAIL_MAILER=log/' /var/www/html/.env
    
    # Configure MEDIA_PATH for Docker
    if ! grep -q "^MEDIA_PATH=" /var/www/html/.env || grep -q "^MEDIA_PATH=$" /var/www/html/.env; then
        if grep -q "^MEDIA_PATH=" /var/www/html/.env; then
            sed -i 's|^MEDIA_PATH=.*|MEDIA_PATH=/var/www/html/media|' /var/www/html/.env
        else
            echo "MEDIA_PATH=/var/www/html/media" >> /var/www/html/.env
        fi
    fi
fi

# Execute koel:init ONLY if the application is not initialized
# Check if the application is already initialized by verifying if the migrations table exists
# This prevents running migrations and seeders every time the container restarts
# Check if there are executed migrations or if the migrations table exists
INITIALIZED=false
if php artisan migrate:status --quiet 2>/dev/null | grep -qE "(Ran|Batch)"; then
    INITIALIZED=true
fi

# Additional verification: if .env file exists and database has content, assume initialized
if [ "$INITIALIZED" = "false" ] && [ -f /var/www/html/.env ]; then
    DB_FILE=$(grep "^DB_DATABASE=" /var/www/html/.env 2>/dev/null | cut -d'=' -f2 | tr -d '"' || echo "")
    if [ -n "$DB_FILE" ] && [ -f "$DB_FILE" ] && [ -s "$DB_FILE" ]; then
        # If the database file exists and has content, check if it has the migrations table
        if sqlite3 "$DB_FILE" "SELECT name FROM sqlite_master WHERE type='table' AND name='migrations';" 2>/dev/null | grep -q "migrations"; then
            INITIALIZED=true
        fi
    fi
fi

if [ "$INITIALIZED" = "false" ]; then
    echo '🚀 Initializing Koel using the official process (composer koel:init)...'
    echo '⚠️  This runs only once. To reinitialize, run: docker exec koel_dev php artisan dev:setup --force'
    composer koel:init -- --no-assets --no-interaction --no-scheduler
else
    echo '✅ Koel is already initialized. Skipping koel:init to protect existing data.'
    echo 'ℹ️  To reinitialize, run: docker exec koel_dev php artisan dev:setup --force'
fi

# Clear Laravel cache in development to ensure fresh changes are reflected
# This ensures that view/config/route cache does not interfere with hot reload
APP_ENV_VAL=$(grep "^APP_ENV=" /var/www/html/.env 2>/dev/null | cut -d'=' -f2 | tr -d '"' || echo "production")
if [ "$APP_ENV_VAL" = "local" ] || [ "$APP_ENV_VAL" = "development" ]; then
  echo '🧹 Clearing Laravel cache for development...'
  php artisan config:clear 2>/dev/null || true
  php artisan view:clear 2>/dev/null || true
  php artisan route:clear 2>/dev/null || true
  php artisan cache:clear 2>/dev/null || true
fi

# Start Laravel + same commands as "pnpm run build" in watch mode (assets via manifest; no HMR)
echo '✅ Starting development server (production build in watch mode)...'
rm -f /var/www/html/public/hot
# Evita ViteManifestNotFoundException: el watch tarda en emitir manifest; la primera petición no debe ir antes.
if [ ! -f /var/www/html/public/build/manifest.json ]; then
  echo '📦 Generando manifest inicial (pnpm run build)...'
  pnpm run build
fi
# Run Laravel server on 0.0.0.0 to be accessible from the host
# Only run queue:listen if QUEUE_CONNECTION is not 'sync' (not needed for sync)
QUEUE_CONN=$(grep "^QUEUE_CONNECTION=" /var/www/html/.env 2>/dev/null | cut -d'=' -f2 || echo "sync")
if [ "$QUEUE_CONN" = "sync" ]; then
  echo 'ℹ️  Queue connection is "sync"; skipping queue:listen to reduce CPU usage'
  exec npx concurrently -k -c "#93c5fd,#fdba74,#c4b5fd" \
    "php artisan serve --host=0.0.0.0 --port=8000" \
    "vp build --watch" \
    "vp build --watch --config vite.config.sw.js" \
    --names=server,build,build-sw \
    --restart-tries=3
else
  echo 'ℹ️  Queue connection is "'"$QUEUE_CONN"'"; starting queue:listen'
  exec npx concurrently -k -c "#93c5fd,#c4b5fd,#fdba74,#a7f3d0" \
    "php artisan serve --host=0.0.0.0 --port=8000" \
    "php artisan queue:listen --tries=1 --sleep=3 --max-time=3600" \
    "vp build --watch" \
    "vp build --watch --config vite.config.sw.js" \
    --names=server,queue,build,build-sw \
    --restart-tries=3
fi
