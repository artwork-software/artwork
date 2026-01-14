#!/bin/bash
set -euo pipefail

if [ "${RUN_INIT:-0}" = "1" ]; then
  if [ -n "${DB_HOST:-}" ]; then
    echo "Warte auf DB ${DB_HOST}:${DB_PORT:-3306}..."
    until nc -z "${DB_HOST}" "${DB_PORT:-3306}"; do sleep 1; done
  fi

  echo "Init..."

  php artisan storage:link || true
  php artisan artwork:container-update || true
  php artisan optimize || true
  rm bootstrap/cache/config.php || true

  npm run build
fi

chown -R www-data:www-data /var/www/html/storage/
chown -R www-data:www-data /var/www/html/bootstrap/cache

touch /tmp/init_ready

exec "$@"
