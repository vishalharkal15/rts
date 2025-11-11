# Use official PHP image with Apache
FROM php:8.2-apache

# Set ServerName globally to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules for Railway
RUN a2enmod rewrite headers env setenvif

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copy and set up entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/database_json \
    && mkdir -p /var/www/html/system/Ch@tr@@m \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/database_json \
    && chmod -R 777 /var/www/html/system

# Expose port (Railway will set PORT env variable)
EXPOSE ${PORT:-80}

# Use custom entrypoint that handles PORT configuration
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
