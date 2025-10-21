FROM php:8.3-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

# Basic php.ini tweaks (optional)
RUN echo "upload_max_filesize=10M" > /usr/local/etc/php/conf.d/uploads.ini \ && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www
