#!/bin/bash

# Carregar variáveis do arquivo .env
if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

# Variáveis de ambiente
DB_HOST=${WORDPRESS_DB_HOST:-localhost}
DB_USER=${WORDPRESS_DB_USER:-root}
DB_PASSWORD=${WORDPRESS_DB_PASSWORD:-root}
DB_NAME=${WORDPRESS_DB_NAME:-bdm_digital_plugin}

# Comando para verificar se o banco está vazio
TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME';" -s --skip-column-names)

# Testa o banco de dados
if [ "$TABLE_COUNT" -eq 0 ]; then
  echo "O banco de dados está vazio. Verificando o arquivo SQL..."

  # Verificar se o arquivo dump.sql existe
  DUMP_FILE="/docker-entrypoint-initdb.d/bdm_digital_plugin.sql"

  if [ -f "$DUMP_FILE" ]; then
    echo "Importando o arquivo SQL..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$DUMP_FILE"
  else
    echo "Arquivo $DUMP_FILE não encontrado. Abortando a importação."
  fi
fi

# Atualizar URLs do WordPress se SITE_URL estiver definido e for diferente do atual
if [ -n "$SITE_URL" ]; then
  CURRENT_URL=$(wp option get siteurl --allow-root)

  if [ "$CURRENT_URL" != "$SITE_URL" ]; then
    echo "Atualizando URLs do WordPress de $CURRENT_URL para $SITE_URL..."
    wp search-replace "$CURRENT_URL" "$SITE_URL" --all-tables --allow-root
  else
    echo "SITE_URL já está correto: $CURRENT_URL"
  fi
else
  echo "Variável SITE_URL não definida. Pulando search-replace."
fi