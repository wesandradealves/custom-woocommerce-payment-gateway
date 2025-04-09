#!/bin/bash

# Carregar variáveis do arquivo .env
if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

# Variáveis de ambiente
DB_HOST=${WORDPRESS_DB_HOST:-localhost}
DB_USER=${WORDPRESS_DB_USER:-root}
DB_PASSWORD=${WORDPRESS_DB_PASSWORD:-root}
DB_NAME=${WORDPRESS_DB_NAME:-wordpress_db}

# Comando para verificar se o banco está vazio
TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME';" -s --skip-column-names)

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
else
  echo "O banco de dados já contém tabelas. Nenhuma importação necessária."
fi