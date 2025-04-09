#!/bin/bash

# Carregar variáveis do arquivo .env, se existir
if [ -f /var/www/.env ]; then
    export $(grep -v '^#' /var/www/.env | xargs)
fi

WPCONFIG="/var/www/html/wp-config.php"
WPCONFIG_TEMPLATE="/var/www/html/wp-config-template.php"

# Certifique-se de que o wp-config.php existe
if [ ! -f "$WPCONFIG" ]; then
    echo "Criando wp-config.php a partir do template..."
    cp "$WPCONFIG_TEMPLATE" "$WPCONFIG"
fi

# Função para adicionar define() antes do comentário final
insert_define() {
  local key="$1"
  local value="$2"
  if ! grep -q "define('$key'," "$WPCONFIG"; then
    echo "Adicionando define('$key', $value) ao wp-config.php"
    sed -i "/^\/\* That's all, stop editing! Happy publishing. \*\//i define('$key', $value);" "$WPCONFIG"
  else
    echo "$key já existe em wp-config.php, pulando..."
  fi
}

# Adicionar constantes básicas de banco de dados
insert_define "DB_NAME" "'${WORDPRESS_DB_NAME}'"
insert_define "DB_USER" "'${WORDPRESS_DB_USER}'"
insert_define "DB_PASSWORD" "'${WORDPRESS_DB_PASSWORD}'"
insert_define "DB_HOST" "'${WORDPRESS_DB_HOST}'"

# Adicionar variáveis úteis
insert_define "JWT_AUTH_CORS_ENABLE" "true"
insert_define "FS_METHOD" "'direct'"

# Gerar JWT_AUTH_SECRET_KEY se não existir
if ! grep -q "JWT_AUTH_SECRET_KEY" "$WPCONFIG"; then
  SECRET_KEY=$(openssl rand -base64 64)
  insert_define "JWT_AUTH_SECRET_KEY" "'$SECRET_KEY'"
else
  echo "JWT_AUTH_SECRET_KEY já existe, pulando..."
fi

# Aguardar banco de dados estar disponível
echo "Aguardando banco de dados em ${WORDPRESS_DB_HOST}..."
until mysqladmin ping -h"${WORDPRESS_DB_HOST}" --silent; do
  echo "Esperando o banco de dados..."
  sleep 5
done

# Verifica se o WordPress está instalado
if ! wp core is-installed --allow-root; then
    echo "WordPress ainda não está instalado."

    # Executa script de inicialização customizado, se existir
    if [ -f /usr/local/bin/init-db.sh ]; then
        echo "Executando init-db.sh..."
        /bin/bash /usr/local/bin/init-db.sh
    fi
else
    echo "WordPress já instalado, pulando instalação."
fi

# Garantir permissões apropriadas
chown www-data:www-data "$WPCONFIG"
chmod 664 "$WPCONFIG"

# Inicializa o Apache
exec docker-entrypoint.sh apache2-foreground
