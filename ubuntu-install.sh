#!/usr/bin/env bash

#Install base software
sudo apt-get update
sudo NEEDRESTART_MODE=a apt-get install -y curl \
 git \
 python3 \
 gcc \
 gosu \
 build-essential \
 ca-certificates \
 gnupg \
 mysql-server \
 mysql-client \
 redis \
 nginx \
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
sudo echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list
##PHP
sudo curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null
sudo echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list

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
       php8.2-memcached php8.2-pcov

# Install composer
sudo curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

#Cleanup apt

sudo apt-get -y autoremove
sudo apt-get clean

#Install artwork

##Delete existing stuff for a clean install
rm -rf /var/www/html/*

##Clone repo and set it up
sudo git clone https://github.com/artwork-software/artwork.git /var/www/html/
sudo cp /var/www/html/.env.examle /var/www/html/.env

### PHP-Config
sudo cp /var/www/html/.install/artwork.pool.conf /etc/php/8.2/fpm/pool.d/artwork.pool.conf

### nginx config
sudo cp /var/www/html/.install/artwork.vhost.conf /etc/nginx/default
composer -d /var/www/html
