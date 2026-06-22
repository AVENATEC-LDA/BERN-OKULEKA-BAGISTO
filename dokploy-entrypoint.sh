#!/bin/bash
set -e

APP_DIR="/var/www/bagisto"

log() {
    echo "[bagisto-dokploy] $(date '+%Y-%m-%d %H:%M:%S') $*"
}

wait_for_mysql() {
    export DB_HOST="${DB_HOST:-mysql}"
    export DB_PORT="${DB_PORT:-3306}"
    export DB_USERNAME="${DB_USERNAME:-bagisto}"
    export DB_PASSWORD="${DB_PASSWORD:-bagisto}"

    log "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."

    for i in $(seq 1 90); do
        if php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }' 2>/dev/null; then
            log "MySQL is reachable."
            return 0
        fi

        sleep 1
    done

    log "ERROR: MySQL is not reachable after 90 seconds."
    return 1
}

cd "$APP_DIR"

if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ -z "$APP_KEY" ]; then
    log "ERROR: APP_KEY is required. Generate one with: php artisan key:generate --show"
    exit 1
fi

wait_for_mysql

php artisan package:discover --ansi --no-interaction
php artisan storage:link --no-interaction 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ] && [ -f storage/installed ]; then
    log "Running database migrations..."
    php artisan migrate --force --no-interaction
elif [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "Skipping automatic migrations because Bagisto is not installed yet. Use the /install flow for the first setup."
fi

if [ "${RUN_INDEXERS:-false}" = "true" ]; then
    log "Running Bagisto indexers..."
    php artisan index:index --mode=full --no-interaction
fi

log "Refreshing optimized Laravel caches..."
php artisan optimize:clear --no-interaction
php artisan optimize --no-interaction

log "Starting services..."
exec "$@"
