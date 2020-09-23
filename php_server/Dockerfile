FROM php:7.2-apache

RUN apt-get update \
    && apt-get -y --no-install-recommends install  \
	php7.2-mysql \
	php-xdebug \
	php7.2-bcmath \
	php7.2-gd \
	php7.2-imap \
	php7.2-ldap \
	git \
	unzip zip \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

run cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
COPY ./CanteenCalandreta_3.3 /var/www/html

COPY  ./php.ini /usr/local/etc/php/php.ini

RUN pear install DB

RUN docker-php-ext-install mysqli
