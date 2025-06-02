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

# Limpar sniffs antigos e configurações antes de instalar o PHPCS/WPCS
RUN composer global remove phpcsstandards/phpcsextra phpcsstandards/phpcsutils dealerdirect/phpcodesniffer-composer-installer || true && \
    rm -rf ~/.composer/vendor/phpcsstandards || true && \
    rm -rf ~/.composer/vendor/modernize || true && \
    rm -rf ~/.composer/vendor/normalizedarrays || true && \
    rm -rf ~/.composer/vendor/universal || true && \
    ~/.composer/vendor/bin/phpcs --config-delete installed_paths || true

# Permitir o plugin do Composer antes de instalar as dependências
RUN composer global config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true && \
    composer global require "squizlabs/php_codesniffer:*" \
    "wp-coding-standards/wpcs:*" \
    "phpcsstandards/phpcsutils:*" \
    "phpcsstandards/phpcsextra:*" \
    "dealerdirect/phpcodesniffer-composer-installer:*" && \
    ~/.composer/vendor/bin/phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs && \
    ~/.composer/vendor/bin/phpcs --config-set default_standard WordPress && \
    ln -sf ~/.composer/vendor/bin/phpcs /usr/local/bin/phpcs && \
    ln -sf ~/.composer/vendor/bin/phpcbf /usr/local/bin/phpcbf

# Verificar e listar os sniffs instalados após configurar o caminho
RUN ~/.composer/vendor/bin/phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs && \
    ~/.composer/vendor/bin/phpcs -i

# Verificar configuração do PHPCS e listar sniffs disponíveis
RUN ~/.composer/vendor/bin/phpcs --config-show && \
    ~/.composer/vendor/bin/phpcs -i

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
RUN php -d memory_limit=-1 /usr/local/bin/wp core download --allow-root --locale=pt_BR --path=/var/www/html

RUN composer require vlucas/phpdotenv && \
    composer config --no-plugins allow-plugins.* true && \
    composer config --global allow-plugins.johnpbloch/wordpress-core-installer true && \
    composer install --no-dev --optimize-autoloader

# Copy only the plugin to be fixed and phpcs.xml first
COPY ./bdmdipag-gateway ./wp-content/plugins/bdmdipag-gateway
COPY phpcs.xml ./
COPY ./bdmdipag-gateway/wpcs-wordpress-mocks.php ./wp-content/plugins/bdmdipag-gateway/wpcs-wordpress-mocks.php

# Corrigir automaticamente o código do plugin conforme o padrão WordPress
WORKDIR /var/www/html

# Reinstalar dependências e listar sniffs disponíveis
RUN composer global update && \
    ~/.composer/vendor/bin/phpcs -i

# Executar PHPCS com bootstrap dos mocks
RUN ~/.composer/vendor/bin/phpcs --standard=phpcs.xml --bootstrap=wp-content/plugins/bdmdipag-gateway/wpcs-wordpress-mocks.php wp-content/plugins/bdmdipag-gateway/

# Now copy in the rest of the plugins and themes
COPY ./woocommerce ./wp-content/plugins/woocommerce
COPY ./storefront ./wp-content/themes/storefront
COPY ./classic-editor ./wp-content/plugins/classic-editor
COPY ./plugin-check ./wp-content/plugins/plugin-check

# Permissões e limpeza
WORKDIR /var/www/html
RUN chown -R www-data:www-data wp-content/plugins && \
    chmod -R 755 wp-content/plugins && \
    rm -rf wp-content/plugins/hello.php wp-content/plugins/hello-dolly wp-content/plugins/akismet

# Uploads
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN mkdir -p /var/www/html/wp-content/uploads && \
    mkdir -p /var/www/html/wp-content && \
    mkdir -p /var/www/html/wp-admin && \
    chown -R www-data:www-data /var/www/html/ && \
    chmod -R 775 /var/www/html/wp-content/uploads && \
    chmod -R 775 /var/www/html/wp-content && \
    chmod -R 775 /var/www/html/wp-admin

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

# Remover sniffs extras de vendors de plugins
RUN rm -rf /var/www/html/wp-content/plugins/plugin-check/vendor/phpcsstandards || true && \
    rm -rf /var/www/html/wp-content/plugins/woocommerce/vendor/phpcsstandards || true

# Corrigir automaticamente o código do plugin conforme o padrão WordPress
WORKDIR /var/www/html

EXPOSE 80

# Entrypoint final
ENTRYPOINT ["/bin/bash", "-c", "/usr/local/bin/setup-wp-config.sh && docker-entrypoint.sh apache2-foreground"]
