# Multi-stage Dockerfile for Edutorium Battle System
# Stage 1: Build stage for PHP dependencies
FROM composer:2.6 AS composer-stage

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Stage 2: Production stage
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    curl \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    curl \
    sockets

# Enable Apache modules
RUN a2enmod rewrite headers ssl

# Set working directory
WORKDIR /var/www/html

# Copy composer dependencies from build stage
COPY --from=composer-stage /app/vendor ./vendor

# Copy application files
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod +x battle-server.php

# Create Apache virtual host configuration
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    ServerName localhost\n\
    \n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    # Enable compression\n\
    LoadModule deflate_module modules/mod_deflate.so\n\
    <Location />\n\
        SetOutputFilter DEFLATE\n\
        SetEnvIfNoCase Request_URI \\\n\
            \\.(?:gif|jpe?g|png)$ no-gzip dont-vary\n\
        SetEnvIfNoCase Request_URI \\\n\
            \\.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary\n\
    </Location>\n\
    \n\
    # Security headers\n\
    Header always set X-Content-Type-Options nosniff\n\
    Header always set X-Frame-Options DENY\n\
    Header always set X-XSS-Protection "1; mode=block"\n\
    Header always set Referrer-Policy "strict-origin-when-cross-origin"\n\
    \n\
    # Cache static assets\n\
    <LocationMatch "\\.(css|js|png|jpg|jpeg|gif|ico|svg)$">\n\
        ExpiresActive On\n\
        ExpiresDefault "access plus 1 month"\n\
        Header append Cache-Control "public"\n\
    </LocationMatch>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Create startup script
RUN echo '#!/bin/bash\n\
echo "Starting Apache..."\n\
apache2-foreground &\n\
\n\
echo "Waiting for Apache to start..."\n\
sleep 5\n\
\n\
echo "Starting WebSocket server..."\n\
cd /var/www/html\n\
php battle-server.php &\n\
\n\
echo "All services started. Waiting for processes..."\n\
wait' > /usr/local/bin/start-services.sh

RUN chmod +x /usr/local/bin/start-services.sh

# Expose ports
EXPOSE 80 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start services
CMD ["/usr/local/bin/start-services.sh"]
