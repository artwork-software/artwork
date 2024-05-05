FROM php:8.2-fpm-bullseye

MAINTAINER "Caldero Systems GmbH"

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

ARG BRANCH
ARG TAG

COPY dockerfiles/init.sh /opt/init.sh

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y ca-certificates  \
    python3 \
    gcc \
    wget \
    curl \
    git \
    sudo \
    gosu \
    nano \
    cron \
    build-essential \
    ca-certificates \
    gnupg \
    mysql-client \
    redis \
    openssl \
    unzip \
    libpng-dev \
    python2 \
    dnsutils \
    librsvg2-bin \
     python3 \
    python3-pip


RUN mkdir -p /etc/apt/keyrings \
    && echo "deb [trusted=yes] https://apt.fury.io/meilisearch/ /" | tee /etc/apt/sources.list.d/fury.list

RUN docker-php-ext-install pdo_mysql bcmath dom intl zip xsl simplexml sysvsem pcntl gd mysqli sockets exif

COPY fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

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

RUN pecl install redis imagick && docker-php-ext-enable redis imagick

RUN COMPOSER_ALLOW_SUPERUSER=1 composer --no-interaction install
RUN php /var/www/html/artisan storage:link

RUN chown -R www-data:www-data /var/www/html
