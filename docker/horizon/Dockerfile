FROM austinkregel/base:latest

COPY start-horizon /usr/local/bin/start-horizon
COPY horizon-supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.1/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-horizon
RUN apt update
RUN apt install mediainfo ffmpeg -y

ENTRYPOINT ["start-horizon"]
