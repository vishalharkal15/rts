#!/bin/bash
set -e

echo "=== RTS Ticket System - Starting Container ==="
echo "Railway PORT: ${PORT:-80}"

# Ensure directories exist with proper permissions
mkdir -p /var/www/html/database_json
mkdir -p /var/www/html/system/Ch@tr@@m
chown -R www-data:www-data /var/www/html
chmod -R 777 /var/www/html/database_json
chmod -R 777 /var/www/html/system

echo "Directories configured successfully"
echo "Starting Apache..."

# Start Apache in foreground
exec apache2-foreground
