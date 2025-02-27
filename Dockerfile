# Use the official PHP image with Apache
FROM php:8.2-apache

# Set environment variables
ENV APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_RUN_PORT=${PORT:-10000} \
    COMPOSER_MEMORY_LIMIT=-1

# Update packages and install required dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    libcurl4-openssl-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    git \
    nano \
    supervisor \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    gd \
    curl \
    mbstring \
    xml \
    zip \
    bcmath \
    exif \
    pcntl \
    soap \
    && rm -rf /var/lib/apt/lists/*

# Fix Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Optimize Apache for performance
RUN echo "MaxRequestWorkers 256" >> /etc/apache2/apache2.conf

# Ensure Apache listens on Render's default PORT
RUN sed -i "s/^Listen .*/Listen ${APACHE_RUN_PORT}/" /etc/apache2/ports.conf \
    && sed -i "s/:80/:${APACHE_RUN_PORT}/g" /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer files first (for caching)
COPY composer.json composer.lock /var/www/html/

# Install dependencies with verbose output
RUN composer install --no-dev --optimize-autoloader --prefer-dist -vvv || cat /var/www/html/composer.log

# Copy application files
COPY . /var/www/html

# Ensure proper permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Security: Disable PHP version exposure
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini

# Set default index to api.php
RUN echo "<?php require 'api.php'; ?>" > /var/www/html/index.php

# Expose the correct port
EXPOSE ${APACHE_RUN_PORT}

# Start Apache in the foreground
CMD ["apache2-foreground"]
