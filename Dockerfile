# Use an official PHP image
FROM php:8.2-apache

# Install required extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Copy all project files into the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
