#!/bin/bash
set -euo pipefail

# --- Dependency check (for bind-mount dev scenario) ---
HASH_DIR="/var/www/html/storage/.dep-hashes"
mkdir -p "$HASH_DIR"

# Composer – ohne Dev-Pakete: Entwicklungswerkzeuge (z. B. laravel/dusk) registrieren eigene Routen
# und Login-Hintertüren, sobald APP_ENV nicht "production" ist. Nur COMPOSER_INSTALL_DEV=1 installiert sie.
COMPOSER_FLAGS="--no-interaction"
if [ "${COMPOSER_INSTALL_DEV:-0}" != "1" ]; then
  COMPOSER_FLAGS="$COMPOSER_FLAGS --no-dev --optimize-autoloader"
fi
if [ ! -d "vendor" ] || [ -z "$(ls -A vendor 2>/dev/null)" ] || \
   ([ -f composer.lock ] && ! md5sum -c "$HASH_DIR/composer.lock.md5" &>/dev/null); then
  echo "Installing Composer dependencies..."
  if [ -f composer.phar ]; then
    php composer.phar install $COMPOSER_FLAGS
  elif command -v composer &>/dev/null; then
    composer install $COMPOSER_FLAGS
  else
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    composer install $COMPOSER_FLAGS
  fi
  md5sum composer.lock > "$HASH_DIR/composer.lock.md5" 2>/dev/null || true
fi

# NPM
if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules 2>/dev/null)" ] || \
   ([ -f package-lock.json ] && ! md5sum -c "$HASH_DIR/package-lock.json.md5" &>/dev/null); then
  echo "Installing Node dependencies..."
  npm install
  md5sum package-lock.json > "$HASH_DIR/package-lock.json.md5" 2>/dev/null || true
fi

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
