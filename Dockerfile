# Use the WordPress base image with PHP 8.2
FROM wordpress:php8.2-apache

# Ensure the /var/www/html directory exists
RUN mkdir -p /var/www/html && chown -R www-data:www-data /var/www/html

# Install system dependencies and PHP extensions required for Composer, WP-CLI, and Node.js
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    ca-certificates \
    nodejs \
    npm \
    && docker-php-ext-configure gd \
    && docker-php-ext-install gd zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally inside the container
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install WP-CLI globally
RUN curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp

# Remove default WordPress files from the image
RUN rm -rf /var/www/html/*

# Set the working directory to the WordPress root directory
WORKDIR /var/www/html

# Copy composer.json into the container
COPY composer.json /var/www/html/composer.json

# Allow all plugins to be used
RUN composer config --no-plugins allow-plugins.* true

# Allow johnpbloch/wordpress-core-installer explicitly
RUN composer config --global allow-plugins.johnpbloch/wordpress-core-installer true

# Install WordPress via Composer (this will install WordPress in the container's wp-content)
RUN composer install

# Copy the custom plugin into the WordPress plugins directory inside the container
COPY ./bdm-digital-payment-gateway /var/www/html/wp-content/plugins/bdm-digital-payment-gateway

# Set the working directory to the plugin directory
WORKDIR /var/www/html/wp-content/plugins/bdm-digital-payment-gateway

# Install npm dependencies and compile SCSS
RUN npm install && npm run build

# Set the working directory to the WordPress root directory
WORKDIR /var/www/html

# Ensure proper permissions on the plugin files
RUN chown -R www-data:www-data /var/www/html/wp-content/plugins && \
    chmod -R 755 /var/www/html/wp-content/plugins

# Ensure proper permissions before deleting
RUN chmod -R 777 /var/www/html/wp-content/plugins && \
    ls -lah /var/www/html/wp-content/plugins && \
    rm -rf /var/www/html/wp-content/plugins/hello.php /var/www/html/wp-content/plugins/hello-dolly /var/www/html/wp-content/plugins/akismet

    
# Copy the .env file into the container
COPY .env /var/www/.env

# Remove default WordPress files from the image
RUN rm -rf /var/www/html/wordpress

RUN composer update

# Expose port 80
EXPOSE 80

