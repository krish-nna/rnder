# Use PHP with Apache as the base image
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Fix Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache mod_rewrite (for clean URLs)
RUN a2enmod rewrite

# Ensure Apache listens on Render's default PORT
ENV PORT 10000
EXPOSE ${PORT}

# Copy the Composer files first (for caching dependencies)
COPY composer.json composer.lock /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy all application files AFTER Composer installation
COPY . /var/www/html

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Set default index to api.php
RUN echo "<?php require 'api.php'; ?>" > /var/www/html/index.php

# Start Apache in the foreground
CMD ["apache2-foreground"]
