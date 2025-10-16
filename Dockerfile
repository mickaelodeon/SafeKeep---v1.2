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

# Copy and setup the Railway wrapper script
COPY railway-wrapper.sh /usr/local/bin/railway-wrapper.sh
RUN chmod +x /usr/local/bin/railway-wrapper.sh

# Create a simple launcher script as fallback
RUN echo '#!/bin/bash\n\
PORT=${PORT:-8080}\n\
echo "SafeKeep launcher - port: $PORT"\n\
exec php -S 0.0.0.0:$PORT -t .' > /usr/local/bin/safekeep-start.sh && \
chmod +x /usr/local/bin/safekeep-start.sh

# Use the wrapper as entrypoint to intercept Railway's commands
ENTRYPOINT ["/usr/local/bin/railway-wrapper.sh"]

# Default command that works
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]