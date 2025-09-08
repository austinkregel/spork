FROM ghcr.io/austinkregel/base:8.4

WORKDIR /var/www/html
COPY . /var/www/html

# We don't want to copy over any secret files or keys, so let's nuke the whole .env and storage directory...
RUN rm -rf .env storage

RUN mkdir -p storage/framework && \
    mkdir -p storage/framework/{cache,sessions,views} && \
    mkdir -p storage/logs && \
    mkdir -p storage/app && \
    mkdir -p storage/app/{email-attachments,keys,public} && \
    mkdir -p /var/www/html/storage/framework/views/


RUN touch database/database.sqlite
RUN cp .env.example .env
RUN cd /var/www/html && ls -alh && composer install
RUN php artisan optimize:clear

RUN apt update && apt install nodejs npm -y
RUN npm install && npm run build && rm -rf node_modules && rm -rf /tmp/*
