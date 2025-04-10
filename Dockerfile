# Base image
FROM wordpress:php8.2-apache

# Criar diretório e ajustar permissões
RUN mkdir -p /var/www/html && chown -R www-data:www-data /var/www/html

# Instalar dependências do sistema e extensões PHP
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
    dos2unix \
  && docker-php-ext-configure gd \
  && docker-php-ext-install gd zip soap \
  && rm -rf /var/lib/apt/lists/*

# Composer
ENV COMPOSER_ROOT_VERSION=6.7.2
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# WP-CLI
RUN curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp

# Remover arquivos padrão do WordPress
RUN rm -rf /var/www/html/*

# WordPress install location
WORKDIR /var/www/html

# Composer setup
COPY composer.json ./
RUN chmod 664 composer.json
RUN wp core download --allow-root --version=6.4 --locale=pt_BR --path=/var/www/html
RUN composer require vlucas/phpdotenv && \
    composer config --no-plugins allow-plugins.* true && \
    composer config --global allow-plugins.johnpbloch/wordpress-core-installer true && \
    composer install --no-dev --optimize-autoloader

# Plugins e temas
COPY ./bdm-digital-payment-gateway ./wp-content/plugins/bdm-digital-payment-gateway
COPY ./woocommerce ./wp-content/plugins/woocommerce
COPY ./storefront ./wp-content/themes/storefront
COPY ./classic-editor ./wp-content/plugins/classic-editor

# Build SCSS do plugin
WORKDIR /var/www/html/wp-content/plugins/bdm-digital-payment-gateway
RUN npm install && npm run build

# Permissões e limpeza
WORKDIR /var/www/html
RUN chown -R www-data:www-data wp-content/plugins && \
    chmod -R 755 wp-content/plugins && \
    rm -rf wp-content/plugins/hello.php wp-content/plugins/hello-dolly wp-content/plugins/akismet

# Uploads
RUN mkdir -p wp-content/uploads && \
    chown -R www-data:www-data wp-content/uploads && \
    chmod -R 775 wp-content/uploads

# Arquivos de configuração
COPY .env /var/www/.env
COPY ./wp-config-template.php /var/www/html/wp-config-template.php
COPY ./setup-wp-config.sh /usr/local/bin/setup-wp-config.sh
RUN dos2unix /usr/local/bin/setup-wp-config.sh && chmod +x /usr/local/bin/setup-wp-config.sh

# Apenas criar o wp-config.php se necessário
RUN [ ! -f wp-config.php ] && cp wp-config-template.php wp-config.php || true

# SQL inicial
COPY ./bdm_digital_plugin.sql /docker-entrypoint-initdb.d/
RUN chmod 644 /docker-entrypoint-initdb.d/bdm_digital_plugin.sql

# Script init-db
COPY ./init-db.sh /usr/local/bin/init-db.sh
RUN dos2unix /usr/local/bin/init-db.sh && chmod +x /usr/local/bin/init-db.sh

# Configuração Apache
COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

EXPOSE 80

# Entrypoint final
ENTRYPOINT ["/bin/bash", "-c", "/usr/local/bin/setup-wp-config.sh && docker-entrypoint.sh apache2-foreground"]