#!/bin/bash
set -e

# Get PORT from Railway environment variable or default to 80
PORT=${PORT:-80}

echo "Starting Apache on port $PORT..."

# Update Apache to listen on the Railway-provided PORT
sed -i "s/Listen 80/Listen 0.0.0.0:$PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost 0.0.0.0:$PORT>/g" /etc/apache2/sites-available/000-default.conf

echo "Apache ports.conf:"
cat /etc/apache2/ports.conf | grep Listen

echo "Apache VirtualHost config:"
head -5 /etc/apache2/sites-available/000-default.conf

# Ensure directories exist with proper permissions
mkdir -p /var/www/html/database_json
mkdir -p /var/www/html/system/Ch@tr@@m
chown -R www-data:www-data /var/www/html
chmod -R 777 /var/www/html/database_json
chmod -R 777 /var/www/html/system

echo "Apache configured to listen on port $PORT"

# Start Apache in foreground
exec apache2-foreground
