#If no APP_KEY exists in the env we generate one
if [ -n "$APP_KEY" ]; then \
    php /var/www/html/artisan key:generate --force; \
fi

#Run migrations
php /var/www/html/artisan migrate --force
php /var/www/html/artisan db:seed:production

start-container
