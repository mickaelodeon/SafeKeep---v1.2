# Use official PHP 8.2 CLI (simpler for Railway)
FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    && docker-php-ext-install \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create inline startup script that handles PORT properly
RUN echo '#!/bin/bash\n\
export PORT=${PORT:-8080}\n\
echo "SafeKeep starting on port $PORT"\n\
exec php -S 0.0.0.0:$PORT -t .' > /usr/local/bin/start-safekeep.sh && \
chmod +x /usr/local/bin/start-safekeep.sh

# Expose port (Railway will set the PORT env var)
EXPOSE $PORT

# Use inline startup script
CMD ["/usr/local/bin/start-safekeep.sh"]