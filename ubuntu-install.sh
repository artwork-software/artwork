#!/usr/bin/env bash

# Funktion zum Ausgeben von Nachrichten
log() {
    echo "[$(date +'%Y-%m-%dT%H:%M:%S%z')]: $*"
}

# Setze die Umgebungsvariable für nicht-interaktive Installationen
export DEBIAN_FRONTEND=noninteractive APT_LISTCHANGES_FRONTEND=none
sudo sh -c 'echo "\$nrconf{restart} = \"a\";" > /etc/needrestart/needrestart.conf'
# Aktualisiere und installiere Basissoftware
log "Aktualisiere Paketlisten und installiere Basissoftware..."
sudo apt-get update -y
sudo apt-get install -o Dpkg::Options::="--force-confdef" \
                     -o Dpkg::Options::="--force-confold" -y curl \
 git \
 python3 \
 gcc \
 wget \
 gosu \
 build-essential \
 ca-certificates \
 gnupg \
 mariadb-server \
 mariadb-client \
 redis \
 nginx \
 openssl \
 unzip \
 supervisor \
 libcap2-bin \
 libpng-dev \
 dnsutils \
 librsvg2-bin \
 fswatch \
 ufw \
 fail2ban \
 unattended-upgrades \
 apparmor-profiles \
 apt-transport-https

# Einrichten automatischer Sicherheitsupdates
log "Konfiguriere automatische Sicherheitsupdates..."
sudo dpkg-reconfigure -f noninteractive unattended-upgrades

# Firewall konfigurieren mit UFW
log "Konfiguriere Firewall mit UFW..."
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable

# Fail2Ban installieren und konfigurieren
log "Installiere und konfiguriere Fail2Ban..."
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Sicherung und bedingte Änderung der SSH-Konfiguration
log "Sichere und konfiguriere SSH..."
read -p "Change SSH auth to keys only? [y/N]: " change_ssh_auth
if [[ "$change_ssh_auth" =~ ^[Yy]$ ]]; then
    log "Ändere SSH-Authentifizierung zu nur Schlüssel..."
    sudo cp /etc/ssh/sshd_config /etc/ssh/sshd_config.bak
    sudo sed -i 's/^#PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
    sudo sed -i 's/^#PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
    sudo systemctl reload sshd
    log "SSH-Authentifizierung auf Schlüssel umgestellt."
else
    log "Änderung der SSH-Authentifizierung übersprungen."
fi


# Installiere nur notwendige Pakete von Repositories
log "Füge benutzerdefinierte Repositories hinzu..."
## Node.js
log "Füge Node.js Repository hinzu..."
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_18.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list

## PHP
log "Füge PHP Repository hinzu..."
curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | sudo gpg --dearmor | sudo tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null
echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" | sudo tee /etc/apt/sources.list.d/ppa_ondrej_php.list

## Meilisearch
log "Füge Meilisearch Repository hinzu..."
echo "deb [trusted=yes] https://apt.fury.io/meilisearch/ /" | sudo tee /etc/apt/sources.list.d/fury.list

# Installiere neue Pakete
log "Aktualisiere Paketlisten und installiere zusätzliche Pakete..."
sudo apt-get update -y
sudo apt-get install -o Dpkg::Options::="--force-confdef" \
                       -o Dpkg::Options::="--force-confold" -y php8.2-cli php8.2-dev php8.2-fpm \
       php8.2-pgsql php8.2-sqlite3 php8.2-gd php8.2-imagick \
       php8.2-curl \
       php8.2-imap php8.2-mysql php8.2-mbstring \
       php8.2-xml php8.2-zip php8.2-bcmath php8.2-soap \
       php8.2-intl php8.2-readline \
       php8.2-ldap \
       php8.2-msgpack php8.2-igbinary php8.2-redis php8.2-swoole \
       php8.2-memcached php8.2-pcov \
       meilisearch=1.9.* \
       nodejs

# Sicherheit für MariaDB konfigurieren
log "Konfiguriere MariaDB für sichere Verbindungen..."
sudo sed -i "s/^bind-address\s*=.*/bind-address = 127.0.0.1/" /etc/mysql/mariadb.conf.d/50-server.cnf
sudo systemctl restart mariadb

# Sicherheit für Redis konfigurieren
log "Konfiguriere Redis für sichere Verbindungen..."
sudo sed -i "s/^bind .*/bind 127.0.0.1/" /etc/redis/redis.conf
REDIS_PASSWORD=$(openssl rand -hex 16)
echo "requirepass $REDIS_PASSWORD" | sudo tee -a /etc/redis/redis.conf
sudo systemctl restart redis

# Cleanup apt
log "Bereinige APT..."
sudo apt-get -y autoremove
sudo apt-get clean

# Installiere Artwork
log "Installiere Artwork..."
# Lösche bestehende Dateien für eine saubere Installation
sudo rm -rf /var/www/html/
sudo mkdir /var/www/html

# Klone das Repository und richte es ein
sudo git clone https://github.com/artwork-software/artwork.git /var/www/html/
sudo cp /var/www/html/.env.standalone.example /var/www/html/.env

# Nginx konfigurieren
log "Konfiguriere Nginx..."
sudo cp -rf /var/www/html/.install/artwork.vhost.conf /etc/nginx/sites-available/default

# Härte Nginx-Konfiguration mit Sicherheits-Headern (HSTS auskommentiert)
sudo bash -c 'cat >> /etc/nginx/sites-available/default <<EOL
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src '\''self'\'';" always;
    # add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
EOL'
sudo systemctl restart nginx

# Hinweis: SSL kann später mit Certbot oder einem anderen Tool konfiguriert werden, sobald die Domain bekannt ist.
# Sobald SSL konfiguriert ist, kann die HSTS-Zeile entkommentiert werden, um die Sicherheit weiter zu erhöhen.


# Setup DB
log "Richte die Datenbank ein..."
MYSQL_PASSWORD=$(openssl rand -hex 24)
sudo mysql -uroot -e "CREATE DATABASE artwork_tools;CREATE USER artwork@\"localhost\" IDENTIFIED BY \"$MYSQL_PASSWORD\"; GRANT ALL PRIVILEGES ON artwork_tools.* TO \"artwork\"@\"localhost\"; FLUSH PRIVILEGES;"
sudo sed -i "s/DB_HOST=db/DB_HOST=localhost/g" /var/www/html/.env
sudo sed -i "s/DB_PASSWORD=artwork/DB_PASSWORD=$MYSQL_PASSWORD/g" /var/www/html/.env

# Setup Meilisearch
log "Richte Meilisearch ein..."
sudo useradd -d /var/lib/meilisearch -s /bin/false -m -r meilisearch
sudo mkdir /var/lib/meilisearch/data /var/lib/meilisearch/dumps /var/lib/meilisearch/snapshots
sudo chown -R meilisearch:meilisearch /var/lib/meilisearch
sudo chmod 750 /var/lib/meilisearch
sudo wget https://raw.githubusercontent.com/meilisearch/meilisearch/latest/config.toml -O /etc/meilisearch.toml
MEILI_KEY=$(openssl rand -hex 16)
sudo echo "MEILISEARCH_KEY=$MEILI_KEY" >> /var/www/html/.env
sudo sed -i "s/env = \"development\"/env = \"production\"/g" /etc/meilisearch.toml
sudo sed -i "s/# master_key = \"YOUR_MASTER_KEY_VALUE\"/master_key = \"$MEILI_KEY\"/g" /etc/meilisearch.toml
sudo sed -i "s/db_path = \".\/data.ms\"/db_path =\"\/var\/lib\/meilisearch\/data\"/g" /etc/meilisearch.toml
sudo sed -i "s/dump_dir = \"dumps\/\"/dump_dir = \"\/var\/lib\/meilisearch\/dumps\"/g" /etc/meilisearch.toml
sudo sed -i "s/snapshot_dir = \"snapshots\/\"/snapshot_dir  = \"\/var\/lib\/meilisearch\/snapshots\"/g" /etc/meilisearch.toml

sudo cp /var/www/html/.install/meilisearch.service /etc/systemd/system/meilisearch.service
sudo systemctl enable meilisearch
sudo systemctl start meilisearch

# Composer installieren
log "Installiere Composer..."
sudo wget -O /var/www/html/composer.phar https://getcomposer.org/download/2.6.5/composer.phar
sudo COMPOSER_ALLOW_SUPERUSER=1 php /var/www/html/composer.phar -d /var/www/html --no-interaction install

# Set Permissions
log "Setze Dateiberechtigungen..."
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 750 /var/www/html
sudo chmod 640 /var/www/html/.env

# Setup Soketi (pusher)
log "Richte Soketi ein..."
PUSHER_KEY=$(openssl rand -hex 16)
PUSHER_SECRET=$(openssl rand -hex 16)
PUSHER_ID=$(openssl rand -hex 16)
sudo cp /var/www/html/.install/artwork-sockets.service /etc/systemd/system/artwork-sockets.service
sudo echo "PUSHER_APP_KEY=$PUSHER_KEY" >> /var/www/html/.env
sudo echo "PUSHER_APP_ID=$PUSHER_ID" >> /var/www/html/.env
sudo echo "PUSHER_APP_SECRET=$PUSHER_SECRET" >> /var/www/html/.env
sudo echo "VITE_PUSHER_APP_KEY=$PUSHER_KEY" >> /var/www/html/.env
sudo echo "VITE_PUSHER_APP_ID=$PUSHER_ID" >> /var/www/html/.env
sudo echo "VITE_PUSHER_APP_SECRET=$PUSHER_SECRET" >> /var/www/html/.env
sudo sed -i "s/__ID/$PUSHER_ID/g" /var/www/html/soketi.config.json
sudo sed -i "s/__KEY/$PUSHER_KEY/g" /var/www/html/soketi.config.json
sudo sed -i "s/__SECRET/$PUSHER_SECRET/g" /var/www/html/soketi.config.json

#Set Redis Password
sudo sed -i "s/REDIS_HOST=redis/REDIS_HOST=localhost/g" /var/www/html/.env
sudo sed -i "s/REDIS_PASSWORD=null/REDIS_PASSWORD=$REDIS_PASSWORD/g" /var/www/html/.env

# Setup Laravel
log "Richte Laravel ein..."
sudo php /var/www/html/artisan key:generate --force
sudo php /var/www/html/artisan storage:link
sudo php /var/www/html/artisan optimize
sudo php /var/www/html/artisan migrate:fresh --force
sudo php /var/www/html/artisan db:seed:production

# Setup JS
log "Installiere und baue JavaScript-Abhängigkeiten..."
sudo npm --prefix /var/www/html install
sudo npm --prefix /var/www/html run build

sudo chown -R www-data:www-data /var/www/html

# Queue Worker
log "Richte Queue Worker ein..."
sudo cp /var/www/html/.install/artwork-worker.service /etc/systemd/system/artwork-worker.service
sudo systemctl daemon-reload
sudo systemctl enable artwork-worker
sudo systemctl enable artwork-sockets

# Indexieren mit Laravel Scout
log "Starte Laravel Scout Indexierung..."
sudo php /var/www/html/artisan scout:index departments
sudo php /var/www/html/artisan scout:index moneysources
sudo php /var/www/html/artisan scout:index shifpresets
sudo php /var/www/html/artisan scout:index shiftpresets
sudo php /var/www/html/artisan scout:index projects
sudo php /var/www/html/artisan scout:index users
sudo php /var/www/html/artisan scout:index freelancers
sudo php /var/www/html/artisan scout:index serviceproviders
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\User\\Models\\User
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\ShiftPreset\\Models\\ShiftPreset
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\Project\\Models\\Project
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\MoneySource\\Models\\MoneySource
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\Freelancer\\Models\\Freelancer
sudo php /var/www/html/artisan scout:import Artwork\\Modules\\ServiceProvider\\Models\\ServiceProvider

# Scheduler (cron)
log "Richte Scheduler ein..."
(crontab -l 2>/dev/null; echo "* * * * * php /var/www/html/artisan schedule:run >> /dev/null 2>&1") | sudo crontab -

# AppArmor-Profil für Nginx hinzufügen
#log "Füge AppArmor-Profil für Nginx hinzu..."
#sudo systemctl enable apparmor
#sudo systemctl start apparmor
# Beispiel: Aktivieren eines vordefinierten Profils oder Erstellen eines benutzerdefinierten Profils
# Hier wird ein Standardprofil angenommen
#sudo aa-enforce /etc/apparmor.d/usr.sbin.nginx

# Abschlussmeldung
log "Installation und Sicherheitskonfiguration abgeschlossen!"
