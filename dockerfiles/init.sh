#!/usr/bin/env bash

#If no APP_KEY exists in the env we generate one
if [ -n "$APP_KEY" ]; then \
    echo "Setting APP_KEY"
    sudo php /var/www/html/artisan key:generate --force; \
fi

#Run migrations
echo "Running migrations"
sudo php /var/www/html/artisan migrate --force
echo "Seed the database if needed"
sudo php /var/www/html/artisan db:seed:production

sudo exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
