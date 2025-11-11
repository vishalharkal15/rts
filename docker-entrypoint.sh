#!/bin/bash
set -e

# Get PORT from Railway environment variable or default to 80
PORT=${PORT:-80}

echo "Starting Apache on port 80 (Railway PORT: $PORT)..."

# Apache will listen on port 80, Railway will handle port forwarding
# Just ensure directories exist with proper permissions
mkdir -p /var/www/html/database_json
mkdir -p /var/www/html/system/Ch@tr@@m
chown -R www-data:www-data /var/www/html
chmod -R 777 /var/www/html/database_json
chmod -R 777 /var/www/html/system

echo "Apache configured to listen on port 80"

# Start Apache in foreground
exec apache2-foreground
