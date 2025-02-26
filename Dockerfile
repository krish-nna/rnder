# Use PHP with Apache as the base image
FROM php:8.2-apache

# Install system dependencies for PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Fix Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache mod_rewrite (if needed for your project)
RUN a2enmod rewrite

# Copy project files into the container
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose the correct port for Render (uses PORT 10000 by default)
EXPOSE 10000

# Ensure Apache listens on the correct port
RUN sed -i "s/Listen 80/Listen 10000/" /etc/apache2/ports.conf
RUN sed -i "s/:80/:10000/g" /etc/apache2/sites-available/000-default.conf

# Start Apache in the foreground
CMD ["apache2-foreground"]
