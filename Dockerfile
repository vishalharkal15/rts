# Use official PHP image with Apache
FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/database_json \
    && mkdir -p /var/www/html/system/Ch@tr@@m \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/database_json \
    && chmod -R 777 /var/www/html/system

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
