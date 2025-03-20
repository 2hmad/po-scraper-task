FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfcgi-bin

# Enable php fpm status page
RUN set -xe && echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/dev dev
RUN mkdir -p /home/dev/.composer && \
    chown -R dev:dev /home/dev

# Set working directory
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application files
COPY . .

# Run Composer again to install the rest of the dependencies
RUN composer dump-autoload --optimize

# Change ownership of the application files
RUN chown -R dev:www-data /var/www/html && \
    find /var/www/html -type f -exec chmod 664 {} \; && \
    find /var/www/html -type d -exec chmod 775 {} \; && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R dev:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure the log file exists and has the correct permissions
RUN touch /var/www/html/storage/logs/laravel.log && \
chown www-data:www-data /var/www/html/storage/logs/laravel.log && \
chmod 775 /var/www/html/storage/logs/laravel.log

# Download and install php-fpm-healthcheck
RUN curl -o /usr/local/bin/php-fpm-healthcheck https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck && \
    chmod +x /usr/local/bin/php-fpm-healthcheck

EXPOSE 9000

# Start php-fpm server
CMD ["php-fpm"]
