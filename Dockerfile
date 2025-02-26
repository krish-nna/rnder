# Use an official PHP image with Apache
FROM php:8.2-apache

# Install required dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-configure pdo_pgsql --with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql

# Copy all project files into the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
