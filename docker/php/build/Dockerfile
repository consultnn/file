FROM php:7.4-fpm-alpine

RUN echo '@community http://dl-cdn.alpinelinux.org/alpine/edge/community' >> /etc/apk/repositories

RUN apk upgrade --update \
    && set -xe \
    && apk add --no-cache \
           imagemagick6 \
           imagemagick6-dev \
           libtool \
           gmp-dev@community \
#           vips-dev@community \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install imagick-3.4.4 \
#    && pecl install vips-1.0.10 \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install gmp exif \
    && apk del .build-deps \
    && curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["php-fpm"]
