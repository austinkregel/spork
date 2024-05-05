FROM austinkregel/base:latest

COPY php.ini /etc/php/8.2/cli/conf.d/99-sail.ini

RUN apt-get update \
    && apt-get install -y cron

COPY basecron /etc/cron.d/basecron
# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/basecron
# Apply cron job
RUN crontab /etc/cron.d/basecron
RUN cat /etc/cron.d/basecron | crontab -
RUN apt update
RUN apt install mediainfo ffmpeg -y
    
# Create the log file to be able to run tail
RUN touch /var/log/cron.log
# Run the command on container startup
CMD cron && echo "[----] Starting crontab." && tail -f /var/log/cron.log
