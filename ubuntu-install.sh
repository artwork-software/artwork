#!/usr/bin/env bash

#Install base software
sudo apt-get update
sudo NEEDRESTART_MODE=a apt-get install -y curl \
 git \
 python3 \
 gcc \
 wget \
 gosu \
 build-essential \
 ca-certificates \
 gnupg \
 mysql-server \
 mysql-client \
 redis \
 nginx \
 openssl \
 unzip \
 supervisor \
 libcap2-bin \
 libpng-dev \
 python2 \
 dnsutils \
 librsvg2-bin \
 fswatch


#Collect all the custom repositories
## Meilisearch
sudo echo "deb [trusted=yes] https://apt.fury.io/meilisearch/ /" | sudo tee /etc/apt/sources.list.d/fury.list
##Node
sudo mkdir -p /etc/apt/keyrings
sudo curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
sudo echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_18.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list
##PHP
sudo curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | sudo gpg --dearmor | sudo tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null
sudo echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" | sudo tee /etc/apt/sources.list.d/ppa_ondrej_php.list

# Install new packages
sudo apt-get update
sudo NEEDRESTART_MODE=a apt-get install -y php8.2-cli php8.2-dev php8.2-fpm \
       php8.2-pgsql php8.2-sqlite3 php8.2-gd php8.2-imagick \
       php8.2-curl \
       php8.2-imap php8.2-mysql php8.2-mbstring \
       php8.2-xml php8.2-zip php8.2-bcmath php8.2-soap \
       php8.2-intl php8.2-readline \
       php8.2-ldap \
       php8.2-msgpack php8.2-igbinary php8.2-redis php8.2-swoole \
       php8.2-memcached php8.2-pcov \
       meilisearch

#Cleanup apt

sudo apt-get -y autoremove
sudo apt-get clean

#Install artwork

##Delete existing stuff for a clean install
sudo rm -rf /var/www/html/
sudo mkdir /var/www/html

##Clone repo and set it up
sudo git clone https://github.com/artwork-software/artwork.git /var/www/html/
sudo cp /var/www/html/.env.standalone.example /var/www/html/.env

## nginx config
sudo cp -rf /var/www/html/.install/artwork.vhost.conf /etc/nginx/sites-available/default
sudo systemctl restart nginx

## Composer
sudo wget -O /var/www/html/composer.phar https://getcomposer.org/download/2.6.5/composer.phar
sudo COMPOSER_ALLOW_SUPERUSER=1 php /var/www/html/composer.phar -d /var/www/html --no-interaction install

## Setup db
PASSWORD=$(openssl rand -hex 24)
sudo mysql -uroot -e "CREATE DATABASE artwork_tools;CREATE USER artwork@\"%\" IDENTIFIED BY \"$PASSWORD\"; GRANT ALL PRIVILEGES ON *.* TO \"artwork\"@\"%\" WITH GRANT OPTION;FLUSH PRIVILEGES;"
sudo sed -i "s/DB_PASSWORD=/DB_PASSWORD=$PASSWORD/g" /var/www/html/.env

sudo chown -R www-data:www-data /var/www/html

#Setup Soketi (pusher)
sudo npm install -g @soketi/soketi
sudo npm install -g pm2
sudo pm2 start soketi -- start
## Setup laravel
sudo php /var/www/html/artisan key:generate --force
sudo php /var/www/html/artisan storage:link
sudo php /var/www/html/artisan migrate:fresh --force
sudo php /var/www/html/artisan db:seed:production

## Setup js
sudo npm --prefix /var/www/html install
#First dev, then prod to bake the keys into soketi(pusher)
sudo npm --prefix /var/www/html run dev
sudo npm --prefix /var/www/html run prod

sudo chown -R www-data:www-data /var/www/html

## Queue Worker
sudo cp /var/www/html/.install/artwork-worker.service /etc/systemd/system/artwork-worker.service
sudo systemctl daemon-reload
sudo systemctl enable artwork-worker

## Scheduler (cron)
(sudo crontab -l 2>/dev/null; echo "* * * * * php /var/www/html/artisan schedule:run") | sudo crontab -
