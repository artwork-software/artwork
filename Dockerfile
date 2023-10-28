FROM ubuntu:22.04

RUN apt-get update

RUN apt-get install -y git curl

#Install meilisearch

RUN curl -L https://install.meilisearch.com | sh



ENTRYPOINT ["tail", "-f", "/dev/null"]
