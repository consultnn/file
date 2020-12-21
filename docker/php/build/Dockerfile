FROM php:7.4-fpm-alpine

RUN apk upgrade --update \
    && set -xe \
    && apk add --no-cache \
           imagemagick \
           imagemagick-dev \
           libtool \
           gmp-dev \
#           vips-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install imagick-3.4.4 \
#    && pecl install vips-1.0.10 \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install gmp exif \
    && apk del .build-deps \
    && curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["php-fpm"]
