#!/usr/bin/env bash
set -e

cd /app

# Render provides these at runtime
if [ -n "$RENDER_EXTERNAL_HOSTNAME" ]; then
    export SANCTUM_STATEFUL_DOMAINS="${RENDER_EXTERNAL_HOSTNAME}"
    export APP_URL="https://${RENDER_EXTERNAL_HOSTNAME}"
fi

php artisan config:clear >/dev/null 2>&1 || true

echo "Running migrations..."
php artisan migrate --force

echo "Ensuring admin account..."
php artisan app:ensure-admin || true

PORT="${PORT:-8000}"
echo "Starting server on 0.0.0.0:${PORT}"
exec php artisan serve --host 0.0.0.0 --port "$PORT"
