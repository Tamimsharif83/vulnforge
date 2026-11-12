FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

COPY docker/app/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/app/zz-vulnforge-logging.ini /usr/local/etc/php/conf.d/zz-vulnforge-logging.ini
COPY app/ /var/www/app/

# A file the normal features never read. Seeing its token in a response proves
# real file read or real command execution occurred.
RUN mkdir -p /opt/forge \
    && echo 'FORGE-SECRET{real_server_side_read_2f9c}' > /opt/forge/flag.txt

RUN mkdir -p /var/www/app/public/uploads \
    && chown -R www-data:www-data /var/www/app/public/uploads

# Log directory: written by www-data, group-readable so the host user can read
# the bind-mounted logs. Group ownership is set to root (host bind mounts map by
# uid/gid); group-write + setgid keep new files readable.
RUN mkdir -p /var/www/app/logs \
    && chown www-data:www-data /var/www/app/logs \
    && chmod 2775 /var/www/app/logs
