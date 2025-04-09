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

WPCONFIG="/var/www/html/wp-config.php"

# Garante que o arquivo existe
if [ ! -f "$WPCONFIG" ]; then
  echo "Erro: $WPCONFIG não encontrado!"
  exit 1
fi

# Função para adicionar uma linha ao wp-config.php se ainda não existir
add_config_line() {
  local key="$1"
  local value="$2"
  if ! grep -q "$key" "$WPCONFIG"; then
    echo "Adicionando $key ao wp-config.php"
    echo "define('$key', $value);" >> "$WPCONFIG"
  else
    echo "$key já existe, pulando..."
  fi
}

# Adicionar JWT_AUTH_SECRET_KEY com valor aleatório se não existir
if ! grep -q "JWT_AUTH_SECRET_KEY" "$WPCONFIG"; then
  SECRET_KEY=$(openssl rand -base64 64)
  echo "define('JWT_AUTH_SECRET_KEY', '$SECRET_KEY');" >> "$WPCONFIG"
else
  echo "JWT_AUTH_SECRET_KEY já existe, pulando..."
fi

# Outras configurações
add_config_line "JWT_AUTH_CORS_ENABLE" "true"
add_config_line "FS_METHOD" "'direct'"

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