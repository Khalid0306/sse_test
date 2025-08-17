FROM richarvey/nginx-php-fpm:latest

ENV APP_ENV=prod
ARG BUILD_VERSION="unknown"
ARG TZ="Europe/Paris"
ARG API_VERSION="@dev"

ENV TZ=${TZ}
ENV BUILD_VERSION=${BUILD_VERSION}

# richarvey information
ENV SKIP_CHOWN=true
ENV RUN_SCRIPTS=1
ENV PHP_MEM_LIMIT=1024M
ENV SKIP_COMPOSER=true

# VDM information
ENV VDM_APP_NAME=default
ENV VDM_PRINT_MSG=false
ENV VDM_MONITORING_TYPE=null
ENV VDM_MONITORING_OPTIONS='{}'
ENV API_VERSION=${API_VERSION}

RUN set -xe \
 && apk update && apk add tzdata \
 && echo $TZ > /etc/TZ \
 && ln -sf /usr/share/zoneinfo/$TZ /etc/localtime \
 && apk add --no-cache --update --virtual .build-deps g++ make autoconf yaml-dev \
 && rm -rf /var/cache/apk/* \
 && apk update

RUN apk add --no-cache --virtual .build-deps \
        g++ \
        make \
        autoconf \
        yaml-dev \
        bash \
        openssh-client \
        wget \
        curl \
        libcurl \
        libzip-dev \
        bzip2-dev \
        openssl-dev \
        git \
        ca-certificates \
        linux-headers \
        libmcrypt-dev \
        libpng-dev \
        libxml2-dev \
        icu-dev \
        libxslt-dev \
        rabbitmq-c-dev \
        postgresql-dev \
    && pecl channel-update pecl.php.net \
    && pecl install apcu amqp-1.11.0 mongodb \
    && mkdir -p /usr/local/etc/php/conf.d \
    && docker-php-ext-enable apcu amqp \
    && docker-php-ext-install sockets \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --version=2.1.5 --install-dir=/usr/local/bin \
    && mv /usr/local/bin/composer.phar /usr/local/bin/composer \
    && php -r "unlink('composer-setup.php');"

COPY . /var/www/html

WORKDIR /var/www/html

RUN cd /var/www/html \
    && composer install --no-scripts --no-dev -n \
    && composer clearcache -n