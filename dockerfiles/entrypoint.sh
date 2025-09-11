#!/bin/bash
set -e

echo "Warte auf die Datenbank..."
while ! nc -z db 3306; do
  sleep 1
done
echo "Datenbank ist verfügbar."

php composer.phar install

php artisan key:generate
php artisan storage:link

php artisan artwork:container-update

npm install
npm run build

npm run sockets &

exec "$@"
