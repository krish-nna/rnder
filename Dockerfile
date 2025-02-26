# Use PHP with Apache as the base image
FROM php:8.2-apache

# Install system dependencies for PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Fix Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache mod_rewrite (for clean URLs)
RUN a2enmod rewrite

# Set Apache to listen on Render's default PORT (10000)
# Ensure Apache listens on the correct port (use ENV variable if available)
RUN sed -i "s/^Listen .*/Listen ${PORT:-10000}/" /etc/apache2/ports.conf \
    && sed -i "s/:80/:${PORT:-10000}/g" /etc/apache2/sites-available/000-default.conf

# Copy project files into the container
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Ensure permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Set default index to api.php
RUN echo "<?php require 'api.php'; ?>" > /var/www/html/index.php

# Expose the correct port
EXPOSE ${PORT}

# Start Apache in the foreground
CMD ["apache2-foreground"]
apt-get update && apt-get install -y php-pgsql
