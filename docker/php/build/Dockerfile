FROM php:8.1-fpm-alpine

RUN apk upgrade --update \
    && set -xe \
    && apk add --no-cache \
           imagemagick \
           libtool \
           libgomp \
#           vips-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS imagemagick-dev gmp-dev libffi-dev \
    && pecl install imagick-3.6.0 \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install gmp exif ffi \
    && apk del .build-deps \
    && curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["php-fpm"]
