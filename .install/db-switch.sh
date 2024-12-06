#!/bin/bash

echo "Dieses Skript migriert die Datenbank von MySQL zu MariaDB. Benutzung auf eigene Gefahr."
echo "Erstellen Sie ein Backup der Datenbank, bevor Sie fortfahren."
echo -e "Haben Sie ein Backup der Datenbank gemacht? (y/n)"
read -r response

if [[ "$response" != "y" ]]; then
    echo "Bitte machen Sie ein Backup, bevor Sie fortfahren."
    exit 1
fi

echo "Bitte geben Sie den vollständigen Pfad zur Backup-Datei an:"
read -r backup_file

if [[ ! -f "$backup_file" ]]; then
    echo "Die angegebene Datei $backup_file existiert nicht. Bitte überprüfen Sie den Pfad."
    exit 1
fi

sudo apt-get update
sudo systemctl stop mysql
sudo apt-get remove --purge mysql-server mysql-client -y
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mariadb-server mariadb-client

DB_PASSWORD=$(grep -oP '^DB_PASSWORD=\K.*' "/var/www/html/.env")

sudo mysql -uroot -e "CREATE DATABASE artwork_tools; \
    CREATE USER 'artwork'@'%' IDENTIFIED BY '$DB_PASSWORD'; \
    GRANT ALL PRIVILEGES ON *.* TO 'artwork'@'%' WITH GRANT OPTION; \
    FLUSH PRIVILEGES;"

sudo mysql -uartwork -p"$DB_PASSWORD" artwork_tools < "$backup_file"

echo "Die Datenbank wurde erfolgreich migriert und wiederhergestellt."