#!/bin/bash
set -e

echo "Warte auf die Datenbank..."
while ! nc -z db 3306; do
  sleep 1
done
echo "Datenbank ist verfügbar."

php artisan artwork:container-update

exec "$@"
