FROM ubuntu:24.04

MAINTAINER "Caldero Systems GmbH"

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

ARG BRANCH
ARG TAG

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y ca-certificates  \
    gcc \
    curl \
    git \
    sudo \
    gosu \
    nano \
    cron \
    build-essential \
    ca-certificates \
    gnupg \
    redis \
    libxml2-dev \
    mysql-client \
    mysql-server \
    openssl \
    unzip \
    libxml2-dev \
    libpng-dev \
    libzip-dev \
    libxslt-dev \
    imagemagick\
    libmagickwand-dev \
    wget \
    htop \
    supervisor \
    dnsutils \
    librsvg2-bin \
    python3 \
    python3-pip

RUN mkdir -p /etc/apt/keyrings \
    && echo "deb [trusted=yes] https://apt.fury.io/meilisearch/ /" | tee /etc/apt/sources.list.d/fury.list \
    && apt-get update \
    && apt-get install -y php-cli php-dev \
           php-pgsql php-sqlite3 php-gd php-imagick \
           php-curl \
           php-imap php-mysql php-mbstring \
           php-xml php-zip php-bcmath php-soap \
           php-intl php-readline \
           php-ldap \
           php-msgpack php-igbinary php-redis \
           php-memcached php-pcov \
           meilisearch=1.9.* \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
               && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_18.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
               && apt-get update \
               && apt-get install -y nodejs \
               && npm install -g npm

RUN git config --global --add safe.directory /var/www/html

RUN git init  \
    && git remote add origin https://github.com/artwork-software/artwork.git  \
    && git pull origin main  \
    && git checkout main \
    && git fetch --all

RUN if [ -n "$TAG" ]; then \
      git checkout tags/$TAG; \
    elif [ -n "$BRANCH" ]; then  \
     git checkout $BRANCH; \
    fi \
    && git pull

RUN curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN npm -g install cross-env @soketi/soketi

RUN COMPOSER_ALLOW_SUPERUSER=1 composer --no-interaction install

RUN php /var/www/html/artisan storage:link

COPY dockerfiles/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY dockerfiles/php/php.ini /etc/php//cli/conf.d/99-artwork.ini
COPY dockerfiles/php/fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY dockerfiles/redis.conf /etc/redis/redis.conf

RUN (crontab -l 2>/dev/null; echo "* * * * * php /var/www/html/artisan schedule:run") | crontab -

RUN npm install

RUN chown -R www-data:www-data /var/www/html
RUN chown -R mysql:mysql /var/lib/mysql

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
