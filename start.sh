#!/bin/bash
# Railway startup script (alternative to Dockerfile)

# Install dependencies if needed
echo "Starting RTS Ticket System..."

# Ensure directories exist
mkdir -p database_json
mkdir -p system/Ch@tr@@m

# Set permissions
chmod -R 777 database_json
chmod -R 777 system

# Start PHP built-in server (for non-Docker deployment)
# Railway will use port from $PORT environment variable
PORT=${PORT:-8000}
echo "Starting server on port $PORT..."
php -S 0.0.0.0:$PORT -t .
