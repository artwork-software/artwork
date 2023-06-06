FROM php:8-fpm-alpine


USER root

WORKDIR /var/www/html

EXPOSE 3341

CMD ["php", "artisan", "websockets:serve", "--port=3341"]

