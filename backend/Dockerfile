FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-client \
    libpq-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd pdo_mysql

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory
COPY . .

# Install dependencies
RUN composer install

# Set correct permissions for storage directory
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Create entrypoint script
RUN echo '#!/bin/bash' > /usr/local/bin/start-laravel.sh && \
    echo 'php artisan migrate' >> /usr/local/bin/start-laravel.sh && \
    echo 'php artisan app:scrape-products' >> /usr/local/bin/start-laravel.sh && \
    echo 'php artisan serve --host=0.0.0.0 --port=8000' >> /usr/local/bin/start-laravel.sh && \
    chmod +x /usr/local/bin/start-laravel.sh

# Expose port 8000
EXPOSE 8000

# Use the entrypoint script
CMD ["/usr/local/bin/start-laravel.sh"]
