FROM node:20-bookworm

MAINTAINER "Caldero Systems GmbH"

WORKDIR '/app'

COPY lang ./lang
COPY package-lock.* .
COPY package.json .
COPY webpack.mix.js .
COPY webpack.config.js .
COPY node_module* ./node_modules
COPY resources ./resources
COPY public ./public

RUN npm -g install cross-env webpack
RUN npm install && npm run dev

ENTRYPOINT ["npm", "run", "watch"]
