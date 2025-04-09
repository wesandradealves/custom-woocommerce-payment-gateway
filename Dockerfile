# filepath: c:\Users\wesan\www\plugin-wordpress\Dockerfile
# Use the WordPress base image with PHP 8.2
FROM wordpress:php8.2-apache

# Ensure the /var/www/html directory exists
RUN mkdir -p /var/www/html && chown -R www-data:www-data /var/www/html

# Install system dependencies and PHP extensions required for Composer, WP-CLI, and Node.js
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    nano \
    libpng-dev \
    libxml2-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    ca-certificates \
    nodejs \
    default-mysql-client \
    npm \
    && docker-php-ext-configure gd \
    && docker-php-ext-install gd zip soap \
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

# Ajustar permissões para o arquivo composer.json
RUN chmod 664 /var/www/html/composer.json 

# Install dotenv via Composer
RUN composer require vlucas/phpdotenv

# Allow all plugins to be used
RUN composer config --no-plugins allow-plugins.* true

# Allow johnpbloch/wordpress-core-installer explicitly
RUN composer config --global allow-plugins.johnpbloch/wordpress-core-installer true

# Install WordPress via Composer (this will install WordPress in the container's wp-content)
RUN if [ -f composer.lock ]; then rm composer.lock; fi && composer install --no-dev --optimize-autoloader

# Copy the custom plugin into the WordPress plugins directory inside the container
COPY ./bdm-digital-payment-gateway /var/www/html/wp-content/plugins/bdm-digital-payment-gateway

# Copying Woocommerce to container
COPY ./woocommerce /var/www/html/wp-content/plugins/woocommerce
COPY ./storefront /var/www/html/wp-content/themes/storefront
COPY ./classic-editor /var/www/html/wp-content/plugins/classic-editor

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

# Ensure the uploads directory exists and is writable
RUN mkdir -p /var/www/html/wp-content/uploads && \
    chown -R www-data:www-data /var/www/html/wp-content/uploads && \
    chmod -R 775 /var/www/html/wp-content/uploads

RUN composer update

# Copy the script to the container
COPY ./setup-wp-config.sh /usr/local/bin/setup-wp-config.sh

# Ensure the script has Unix line endings
RUN apt-get update && apt-get install -y dos2unix && dos2unix /usr/local/bin/setup-wp-config.sh

# Ensure the script is executable
RUN chmod +x /usr/local/bin/setup-wp-config.sh

# Copy the SQL file to the container
COPY ./bdm_digital_plugin.sql /docker-entrypoint-initdb.d/bdm_digital_plugin.sql

# Set proper permissions for the SQL file
RUN chmod 644 /docker-entrypoint-initdb.d/bdm_digital_plugin.sql

# Set the script as the ENTRYPOINT
ENTRYPOINT ["/usr/local/bin/setup-wp-config.sh"]

# Copiar o script de inicialização para o contêiner
COPY ./init-db.sh /usr/local/bin/init-db.sh
RUN dos2unix /usr/local/bin/init-db.sh && chmod +x /usr/local/bin/init-db.sh

# Expose port 80
EXPOSE 80