FROM node:18-bullseye

RUN apt update && apt upgrade -y

RUN apt install -y git libolm-dev libjs-olm

CMD cd /var/www/html && npm i && npm upgrade @matrix-org/olm && node /var/www/html/docker/matrix-bot/index.js
