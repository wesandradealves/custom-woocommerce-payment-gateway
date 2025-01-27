# Extend the official WordPress image
FROM wordpress:latest

# Optional: Install additional PHP extensions or tools
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Install Composer
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install vlucas/phpdotenv for environment variable support
COPY composer.json composer.lock /var/www/html/
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose WordPress default port
EXPOSE 80