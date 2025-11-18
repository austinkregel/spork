FROM ghcr.io/austinkregel/base:8.3
USER root

RUN apk add bash

RUN addgroup -g 1000 sail && \
    adduser -D -u 1000 -G sail sail

WORKDIR /var/www/html
ADD . /var/www/html

COPY basecron /etc/cron.d/basecron

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php83/conf.d/99-sail.ini

EXPOSE 6001
EXPOSE 80
RUN git config --global --add safe.directory /var/www/html

ENTRYPOINT ["start-container"]
