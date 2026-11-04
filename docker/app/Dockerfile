FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

COPY docker/app/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY app/ /var/www/app/

RUN mkdir -p /var/www/app/public/uploads \
    && chown -R www-data:www-data /var/www/app/public/uploads
