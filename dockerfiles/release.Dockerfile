FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV    VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
ENV    VITE_PUSHER_HOST="${PUSHER_HOST}"
ENV    VITE_PUSHER_APP_ID='${PUSHER_APP_ID}'
ENV    VITE_PUSHER_APP_SECRET='${PUSHER_APP_SECRET}'

RUN apt-get update -y && \
    apt-get install -y --no-install-recommends \
      curl \
      git \
      python3 \
      gcc \
      wget \
      gosu \
      build-essential \
      ca-certificates \
      gnupg \
      nginx \
      openssl \
      unzip \
      netcat \
      supervisor \
      libcap2-bin \
      libpng-dev \
      dnsutils \
      librsvg2-bin \
      fswatch \
      apt-transport-https \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /etc/apt/keyrings && \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
        | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_18.x nodistro main" \
        > /etc/apt/sources.list.d/nodesource.list && \
    curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' \
        | gpg --dearmor \
        > /etc/apt/keyrings/ppa_ondrej_php.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" \
        > /etc/apt/sources.list.d/ppa_ondrej_php.list && \
    apt-get update -y && \
    apt-get install -y --no-install-recommends \
        php8.2-cli php8.2-dev php8.2-fpm \
        php8.2-pgsql php8.2-sqlite3 php8.2-gd php8.2-imagick \
        php8.2-curl \
        php8.2-imap php8.2-mysql php8.2-mbstring \
        php8.2-xml php8.2-zip php8.2-bcmath php8.2-soap \
        php8.2-intl php8.2-readline \
        php8.2-ldap \
        php8.2-msgpack php8.2-igbinary php8.2-redis php8.2-swoole \
        php8.2-memcached php8.2-pcov \
        nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/html && \
    mkdir -p /var/log/supervisor

WORKDIR /var/www/html
COPY . /var/www/html

RUN cp .env.standalone.example .env || true

RUN cp -rf .install/artwork.vhost.conf /etc/nginx/sites-available/default && \
    echo "\n    add_header X-Frame-Options \"SAMEORIGIN\" always;" \
    "\n    add_header X-XSS-Protection \"1; mode=block\" always;" \
    "\n    add_header X-Content-Type-Options \"nosniff\" always;" \
    "\n    add_header Referrer-Policy \"no-referrer-when-downgrade\" always;" \
    "\n    add_header Content-Security-Policy \"default-src 'self';\" always;" \
    "\n    # add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\" always;\n" \
    >> /etc/nginx/sites-available/default

RUN wget -O composer.phar https://getcomposer.org/download/2.6.5/composer.phar && \
    php composer.phar --no-interaction install

RUN php artisan key:generate && php artisan storage:link

RUN npm install && npm run build

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 750 /var/www/html && \
    chmod 640 /var/www/html/.env || true
EXPOSE 80

COPY dockerfiles/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["sh", "-c", "php-fpm8.2 -F & nginx -g 'daemon off;'"]
