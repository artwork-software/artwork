FROM node:20-bookworm as node-compiler

ARG BRANCH=''
ARG TAG=''

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

FROM nginx:1.21

MAINTAINER "Caldero Systems GmbH"

COPY dockerfiles/nginx/conf/nginx.vhost.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html/public

COPY --from=node-compiler /app/public/js /var/www/html/public/js
