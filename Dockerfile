# Use PHP with Apache as the base image
FROM php:8.2-apache

# Install system dependencies for PostgreSQL and Zip support
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pgsql pdo_pgsql zip

# Fix Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache mod_rewrite (for clean URLs)
RUN a2enmod rewrite

# Ensure Apache listens on Render's default PORT
ENV APACHE_RUN_PORT=${PORT:-10000}
RUN sed -i "s/^Listen .*/Listen ${APACHE_RUN_PORT}/" /etc/apache2/ports.conf \
    && sed -i "s/:80/:${APACHE_RUN_PORT}/g" /etc/apache2/sites-available/000-default.conf

# Copy composer.json and composer.lock into the container
COPY composer.json composer.lock /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the application files
COPY . /var/www/html

# Ensure permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Set default index to api.php
RUN echo "<?php require 'api.php'; ?>" > /var/www/html/index.php

# Expose the correct port
EXPOSE ${APACHE_RUN_PORT}

# Start Apache in the foreground
CMD ["apache2-foreground"]