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

# Adicionar configurações ao wp-config.php
echo "define('JWT_AUTH_SECRET_KEY', '$(openssl rand -base64 64)');" >> /var/www/html/wp-config.php
echo "define('JWT_AUTH_CORS_ENABLE', true);" >> /var/www/html/wp-config.php
echo "define('FS_METHOD', 'direct');" >> /var/www/html/wp-config.php

# Aguarda o banco estar pronto
echo "Aguardando banco de dados..."
until wp db check --allow-root; do
  echo "Esperando o banco de dados..."
  sleep 5
done

# Define a URL nova (ajuste conforme seu ambiente)
NEW_URL="http://54.207.73.19:8000"

# Verifica se o WordPress já está instalado
if wp core is-installed --allow-root; then
  echo "Atualizando URLs do WordPress para ${SITE_URL}..."
  # Instalar WordPress se ainda não estiver instalado
    if ! wp core is-installed --allow-root; then
    echo "Instalando WordPress..."
    wp core install \
        --url="${SITE_URL}" \
        --title="Meu Site WP" \
        --admin_user="${WORDPRESS_USER}" \
        --admin_password="${WORDPRESS_PWD}" \
        --admin_email="admin@example.com" \
        --skip-email \
        --allow-root
    fi
  wp search-replace "$(wp option get siteurl --allow-root)" "${SITE_URL}" --all-tables --allow-root
else
  echo "WordPress ainda não instalado. Ignorando search-replace."
fi


# Iniciar o servidor Apache
exec apache2-foreground