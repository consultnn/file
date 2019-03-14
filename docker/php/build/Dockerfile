FROM php:7.2.4-fpm-alpine3.7

RUN echo '@community http://dl-cdn.alpinelinux.org/alpine/edge/community' >> /etc/apk/repositories

RUN apk upgrade --update

RUN apk --no-cache add \
    git

RUN set -xe \
    && apk add --no-cache \
           imagemagick6 \
           imagemagick6-dev \
           libtool \
        && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        && pecl install imagick-3.4.3 \
        && echo "extension=imagick.so" > /usr/local/etc/php/conf.d/imagick.ini

RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer --version=1.0.3

RUN apk add --no-cache \
    gmp-dev@community \
    && docker-php-ext-install gmp \
    && docker-php-ext-install exif \
    && echo "extension=/usr/lib/php7/modules/gmp.so" > /usr/local/etc/php/conf.d/00_gmp.ini

RUN apk del .build-deps

CMD ["php-fpm"]
