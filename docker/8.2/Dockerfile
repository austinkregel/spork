FROM austinkregel/base:latest

WORKDIR /var/www/html
ADD . /var/www/html

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.2/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

RUN apt update && apt install --fix-missing
# For Meta data/media information spelunking.
RUN apt install mediainfo ffmpeg -y
RUN apt update && apt install --fix-missing

RUN apt install -y wget libicu-dev libxml2-dev libfontconfig bzip2 zlib1g-dev libcurl4-gnutls-dev libtidy-dev build-essential autoconf automake libtool bison re2c pkg-config libvirt-dev libxml2-dev xsltproc libxml2-utils libmagickwand-dev

RUN apt update && apt install --fix-missing

ENV LIBVIRT_PHP_VERSION 0.5.8
RUN apt-get -y install wget \
  && pecl install imagick  \
  && ls -lah \
  && cd ./libvirt-php/ \
  && ./autogen.sh \
  && make clean \
  && make \
  && make install \
  && rm -rf /tmp/pear
RUN groupadd -g139 -f libvirt && usermod -a -G libvirt sail
RUN groupadd kvm && usermod -a -G kvm sail


EXPOSE 8000

ENTRYPOINT ["start-container"]
