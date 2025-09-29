FROM ghcr.io/austinkregel/base:8.4

WORKDIR /var/www/html
ADD . /var/www/html

COPY basecron /etc/cron.d/basecron

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/${PHP_VERSION}/cli/conf.d/99-sail.ini

EXPOSE 6001
EXPOSE 80

ENTRYPOINT ["start-container"]
