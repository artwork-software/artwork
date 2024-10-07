#!/usr/bin/env bash

#Update OS
sudo apt-get update
sudo NEEDRESTART_MODE=a apt-get dist-upgrade -y

#Get new code
sudo git -C /var/www/html pull

#Install dependencies
sudo COMPOSER_ALLOW_SUPERUSER=1 php /var/www/html/composer.phar -d /var/www/html --no-interaction install

sudo chown -R www-data:www-data /var/www/html

#Clear cache and update db
sudo php /var/www/html/artisan cache:clear
sudo php /var/www/html/artisan optimize
sudo php /var/www/html/artisan migrate --force

## Setup js
sudo npm --prefix /var/www/html install
#First dev, then prod to bake the keys into soketi(pusher)
sudo npm --prefix /var/www/html run build

sudo chown -R www-data:www-data /var/www/html

sudo php /var/www/html/artisan scout:index departments
sudo php /var/www/html/artisan scout:index moneysources
sudo php /var/www/html/artisan scout:index shifpresets
sudo php /var/www/html/artisan scout:index shiftpresets
sudo php /var/www/html/artisan scout:index projects
sudo php /var/www/html/artisan scout:index users
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\User\\Models\\User
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\ShiftPreset\\Models\\ShiftPreset
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\Project\\Models\\Project
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\MoneySource\\Models\\MoneySource

sudo systemctl restart artwork-worker
sudo systemctl restart meilisearch
