FROM ghcr.io/austinkregel/base:8.2

WORKDIR /var/www/html
ADD . /var/www/html

RUN apt update && apt install --fix-missing
# For Meta data/media information spelunking.
RUN apt install cron -y

COPY basecron /etc/cron.d/basecron
# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/basecron

RUN crontab /etc/cron.d/basecron
# So I want to remove the cron service from the container, because
# I'll have supervisord running the cron service for better logging.
RUN update-rc.d cron remove
RUN userdel -r ubuntu
RUN userdel -r sail
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/${PHP_VERSION}/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 6001
EXPOSE 80

ENTRYPOINT ["start-container"]
