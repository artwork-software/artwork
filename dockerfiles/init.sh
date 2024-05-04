#!/usr/bin/env bash

#If no APP_KEY exists in the env we generate one
if [ -n "$APP_KEY" ]; then \
    sudo php /var/www/html/artisan key:generate --force; \
fi

#Run migrations
sudo php /var/www/html/artisan migrate --force
sudo php /var/www/html/artisan db:seed:production

sudo start-container
