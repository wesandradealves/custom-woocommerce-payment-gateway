#!/bin/bash

# Carregar variáveis do arquivo .env, se existir
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Certifique-se de que o wp-config.php existe
if [ ! -f /var/www/html/wp-config.php ]; then
    cp /var/www/html/wp-config-sample.php /var/www/html/wp-config.php
fi

# Substituir placeholders no wp-config.php com variáveis de ambiente
sed -i "s/database_name_here/${WORDPRESS_DB_NAME}/" /var/www/html/wp-config.php
sed -i "s/username_here/${WORDPRESS_DB_USER}/" /var/www/html/wp-config.php
sed -i "s/password_here/${WORDPRESS_DB_PASSWORD}/" /var/www/html/wp-config.php
sed -i "s/localhost/${WORDPRESS_DB_HOST}/" /var/www/html/wp-config.php

CONFIG_FILE="/var/www/html/wp-config.php"

# Adicionar as definições apenas se ainda não existirem
if ! grep -q "JWT_AUTH_SECRET_KEY" "$CONFIG_FILE"; then
    echo "" >> "$CONFIG_FILE"
    echo "// JWT Auth Config" >> "$CONFIG_FILE"
    echo "define('JWT_AUTH_SECRET_KEY', '$(openssl rand -base64 64)');" >> "$CONFIG_FILE"
    echo "define('JWT_AUTH_CORS_ENABLE', true);" >> "$CONFIG_FILE"
    echo "define('FS_METHOD', 'direct');" >> "$CONFIG_FILE"
fi

# Aguarda o banco estar pronto
echo "Aguardando banco de dados..."
until mysqladmin ping -h"${WORDPRESS_DB_HOST}" --silent; do
  echo "Esperando o banco de dados..."
  sleep 5
done

# Verifica se o WordPress já está instalado
if ! wp core is-installed --allow-root; then
    # echo "Instalando WordPress..."
    # wp core install \
    #--url="${SITE_URL}" \
    #--title="Meu Site WP" \
    #--admin_user="${WORDPRESS_USER}" \
    #--admin_password="${WORDPRESS_PWD}" \
    #--admin_email="admin@example.com" \
    #--skip-email \
    #--allow-root 
    if [ -f /usr/local/bin/init-db.sh ]; then
        echo "Executando init-db.sh..."
        /bin/bash /usr/local/bin/init-db.sh
    fi
    # else
    #echo "Atualizando URLs do WordPress para ${SITE_URL}..."
    #wp search-replace "$(wp option get siteurl --allow-root)" "${SITE_URL}" --all-tables --allow-root 
fi

# Inicializa o Apache
exec docker-entrypoint.sh apache2-foreground