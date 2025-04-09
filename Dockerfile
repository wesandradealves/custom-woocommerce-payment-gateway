# Use the WordPress base image with PHP 8.2
FROM wordpress:php8.2-apache

# Criar diretório padrão e ajustar permissões
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
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# WP-CLI
RUN curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp

# Remover arquivos padrão
RUN rm -rf /var/www/html/*

WORKDIR /var/www/html

# Copiar arquivos Composer
COPY composer.json /var/www/html/composer.json

# Ajustar permissões
RUN chmod 664 /var/www/html/composer.json

# Instalar dependências via Composer
RUN composer require vlucas/phpdotenv && \
    composer config --no-plugins allow-plugins.* true && \
    composer config --global allow-plugins.johnpbloch/wordpress-core-installer true && \
    composer install --no-dev --optimize-autoloader

# Copiar plugins e temas
COPY ./bdm-digital-payment-gateway /var/www/html/wp-content/plugins/bdm-digital-payment-gateway
COPY ./woocommerce /var/www/html/wp-content/plugins/woocommerce
COPY ./storefront /var/www/html/wp-content/themes/storefront
COPY ./classic-editor /var/www/html/wp-content/plugins/classic-editor

# Instalar npm e buildar SCSS
WORKDIR /var/www/html/wp-content/plugins/bdm-digital-payment-gateway
RUN npm install && npm run build

# Permissões e remoção de plugins desnecessários
WORKDIR /var/www/html
RUN chown -R www-data:www-data wp-content/plugins && \
    chmod -R 755 wp-content/plugins && \
    rm -rf wp-content/plugins/hello.php wp-content/plugins/hello-dolly wp-content/plugins/akismet

# Criar diretório uploads com permissões
RUN mkdir -p wp-content/uploads && \
    chown -R www-data:www-data wp-content/uploads && \
    chmod -R 775 wp-content/uploads

# Copiar arquivo .env
COPY .env /var/www/.env

# Copiar template de wp-config.php e script de configuração
COPY ./wp-config-template.php /var/www/html/wp-config-template.php
COPY ./setup-wp-config.sh /usr/local/bin/setup-wp-config.sh
RUN dos2unix /usr/local/bin/setup-wp-config.sh && chmod +x /usr/local/bin/setup-wp-config.sh

# Apenas cria o wp-config.php se ainda não existir
RUN if [ ! -f /var/www/html/wp-config.php ]; then cp wp-config-template.php wp-config.php; fi

# Copiar SQL de dados iniciais
COPY ./bdm_digital_plugin.sql /docker-entrypoint-initdb.d/bdm_digital_plugin.sql
RUN chmod 644 /docker-entrypoint-initdb.d/bdm_digital_plugin.sql

# Script de inicialização do banco
COPY ./init-db.sh /usr/local/bin/init-db.sh
RUN dos2unix /usr/local/bin/init-db.sh && chmod +x /usr/local/bin/init-db.sh

# Apache customizado
COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Expõe porta
EXPOSE 80

# Script de entrada
ENTRYPOINT ["/usr/local/bin/setup-wp-config.sh"]
