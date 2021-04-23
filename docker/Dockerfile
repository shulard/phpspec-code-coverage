FROM php:7.3-cli-alpine3.12

# SYS: Install required packages
RUN apk --no-cache upgrade && \
    apk --no-cache add bash git sudo openssh autoconf gcc g++ make gettext make

# COMPOSER: install binary
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# PHP: Install php extensions
RUN pecl channel-update pecl.php.net && \
    pecl install pcov && \
    docker-php-ext-enable pcov

WORKDIR /var/www/html
