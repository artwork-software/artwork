FROM node:20-bookworm

MAINTAINER "Caldero Systems GmbH"

WORKDIR '/app'

COPY lang ./lang
COPY package-lock.* .
COPY package.json .
COPY vite.config.js .
COPY resources ./resources
COPY public ./public

RUN npm install

ENTRYPOINT ["npm", "run", "dev"]
