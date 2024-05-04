FROM node:20-bookworm as node-compiler

ARG BRANCH
ARG TAG

WORKDIR '/app'

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN apt-get update && apt-get install -y ca-certificates

RUN apt-get update && apt-get install -y git \
    && git clone https://github.com/artwork-software/artwork.git .

RUN if [ -n "$BRANCH"]; then \
     git checkout $BRANCH; \
    elif [ -n "$TAG" ]; then  \
      git checkout tags/$TAG; \
    fi


RUN npm -g install cross-env webpack
RUN npm install && npm run dev && npm run prod

FROM ubuntu:22.04 as base

MAINTAINER "Caldero Systems GmbH"

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

ARG BRANCH
ARG TAG

COPY start-container /usr/local/bin/start-container
COPY supervisor.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y ca-certificates  \
    python3 \
    gcc \
    wget \
    curl \
    git \
    gosu \
    cron \
    build-essential \
    ca-certificates \
    gnupg \
    mysql-client \
    redis \
    nginx \
    openssl \
    unzip \
    libpng-dev \
    supervisor \
    python2 \
    dnsutils \
    librsvg2-bin

RUN mkdir -p /etc/apt/keyrings \
    && echo "deb [trusted=yes] https://apt.fury.io/meilisearch/ /" | tee /etc/apt/sources.list.d/fury.list \
    && echo "deb [trusted=yes] https://ppa.launchpadcontent.net/ondrej/php/ubuntu/ jammy main " | tee /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && echo "deb-src [trusted=yes] https://ppa.launchpadcontent.net/ondrej/php/ubuntu/ jammy main " >> /etc/apt/sources.list.d/ppa_ondrej_php.list

RUN apt-get update \
    && apt-get install -y php8.2-cli php8.2-dev \
       php8.2-pgsql php8.2-sqlite3 php8.2-gd php8.2-imagick \
       php8.2-curl \
       php8.2-imap php8.2-mysql php8.2-mbstring \
       php8.2-xml php8.2-zip php8.2-bcmath php8.2-soap \
       php8.2-intl php8.2-readline \
       php8.2-ldap \
       php8.2-msgpack php8.2-igbinary php8.2-redis php8.2-swoole \
       php8.2-memcached

RUN git init  \
    && git remote add origin https://github.com/artwork-software/artwork.git  \
    && git pull origin main  \
    && git checkout main

RUN curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN if [ -n "$BRANCH"]; then \
     git checkout $BRANCH; \
    elif [ -n "$TAG" ]; then  \
      git checkout tags/$TAG; \
    fi

RUN cp -rf /var/www/html/.install/artwork.vhost.conf /etc/nginx/sites-available/default

RUN COMPOSER_ALLOW_SUPERUSER=1 composer --no-interaction install
RUN php /var/www/html/artisan storage:link

COPY --from=node-compiler /app/public/js /var/www/html/public/js

RUN chown -R www-data:www-data /var/www/html

RUN (crontab -l 2>/dev/null; echo "* * * * * php /var/www/html/artisan schedule:run") | crontab -

ENTRYPOINT ["start-container"]
