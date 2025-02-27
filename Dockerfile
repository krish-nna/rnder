# Use PHP with Apache as the base image
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql intl gd curl mbstring xml zip

# Fix Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache mod_rewrite (for clean URLs)
RUN a2enmod rewrite

# Set memory limit to prevent Composer crashes
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini

# Ensure Apache listens on Render's default PORT
ENV PORT 10000
EXPOSE ${PORT}

# Copy composer.json and composer.lock first (to cache dependencies)
COPY composer.json composer.lock /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Ensure permissions for Apache and Composer
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install dependencies as the correct user
USER www-data
RUN composer install --no-dev --optimize-autoloader
USER root

# Copy the rest of the application files
COPY . /var/www/html

# Ensure permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Set default index to api.php
RUN echo "<?php require 'api.php'; ?>" > /var/www/html/index.php

# Start Apache in the foreground
CMD ["apache2-foreground"]
