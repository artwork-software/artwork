FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
RUN apt-get update -y && \
    apt-get install -y --no-install-recommends \
      curl \
      git \
      python3 \
      nano \
      gcc \
      wget \
      gosu \
      build-essential \
      ca-certificates \
      gnupg \
      nginx \
      openssl \
      unzip \
      mariadb-client \
      netcat-openbsd \
      supervisor \
      libcap2-bin \
      libpng-dev \
      dnsutils \
      librsvg2-bin \
      fswatch \
      apt-transport-https \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 22.x and PHP 8.4
RUN mkdir -p /etc/apt/keyrings && \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
        | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" \
        > /etc/apt/sources.list.d/nodesource.list && \
    curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' \
        | gpg --dearmor \
        > /etc/apt/keyrings/ppa_ondrej_php.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" \
        > /etc/apt/sources.list.d/ppa_ondrej_php.list && \
    apt-get update -y && \
    apt-get install -y --no-install-recommends \
        php8.4-cli php8.4-dev php8.4-fpm \
        php8.4-pgsql php8.4-sqlite3 php8.4-gd php8.4-imagick \
        php8.4-curl \
        php8.4-imap php8.4-mysql php8.4-mbstring \
        php8.4-xml php8.4-zip php8.4-bcmath php8.4-soap \
        php8.4-intl php8.4-readline \
        php8.4-ldap \
        php8.4-msgpack php8.4-igbinary php8.4-redis php8.4-swoole \
        php8.4-memcached php8.4-pcov \
        nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/html && \
    mkdir -p /var/log/supervisor

WORKDIR /var/www/html

# Copy entrypoint script first (from meta repo)
COPY dockerfiles/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY dockerfiles/php.ini /etc/php/8.4/fpm/conf.d/99-custom.ini
COPY dockerfiles/nginx.conf /etc/nginx/nginx.conf

# Copy only the artwork application (not the meta repository files)
COPY --chown=www-data:www-data . /var/www/html/

# Install Composer dependencies
RUN if [ -f composer.phar ]; then \
        php composer.phar install --no-dev --optimize-autoloader --no-interaction; \
    elif [ -f /usr/local/bin/composer ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    else \
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

RUN npm install

# Configure nginx
RUN cp -rf dockerfiles/artwork-php.84.vhost.conf /etc/nginx/sites-available/default
# Set permissions on writable directories only
RUN chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["sh", "-c", "php-fpm8.4 -F & nginx -g 'daemon off;'"]
