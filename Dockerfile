# Base WordPress image
FROM wordpress:latest

# Install required tools and PHP extensions
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    && docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory to the WordPress root directory
WORKDIR /var/www/html

# Copy Composer files to the container root
COPY ./composer.json ./composer.lock ./

# Copy `index.php` and `wp-config.php` to the container root
COPY ./index.php ./wp-config.php ./

# Copy the `wp-content` folder to the container root
COPY ./wp-content ./wp-content

# Copy the WordPress core files to the `/wordpress` directory
COPY ./wordpress ./wordpress

# Run Composer to install dependencies
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Configure Apache to serve from the wordpress directory
COPY ./apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose WordPress default port
EXPOSE 80

# Start the container with Apache
CMD ["apache2-foreground"]