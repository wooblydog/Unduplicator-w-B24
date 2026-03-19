
FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite

RUN mkdir -p /var/www/html/logs && chown -R www-data:www-data /var/www/html/logs

EXPOSE 80

