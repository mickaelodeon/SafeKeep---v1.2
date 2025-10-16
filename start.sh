#!/bin/bash
# Start script for Railway deployment

# Set default port if not provided
PORT=${PORT:-8080}

echo "Starting SafeKeep on port $PORT"

# Start PHP built-in server
php -S 0.0.0.0:$PORT -t .