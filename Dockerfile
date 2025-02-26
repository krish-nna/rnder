# Use the official PHP image with Apache
FROM php:8.2-apache

# Enable necessary PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Copy project files into the container
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (default for Apache)
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
