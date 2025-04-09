#!/bin/bash

echo "Executando init-db.sh para inicializar o banco de dados e configurar URLs..."

# Carregar variáveis do arquivo .env
if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

# Variáveis de ambiente
DB_HOST=${WORDPRESS_DB_HOST:-localhost}
DB_USER=${WORDPRESS_DB_USER:-root}
DB_PASSWORD=${WORDPRESS_DB_PASSWORD:-root}
DB_NAME=${WORDPRESS_DB_NAME:-bdm_digital_plugin}

# Verifica se o banco existe
if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME;" 2>/dev/null; then
  echo "Banco de dados '$DB_NAME' não existe. Abortando init-db."
  exit 1
fi

# Verifica se o banco está vazio
TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME';" -s --skip-column-names)

if [ "$TABLE_COUNT" -eq 0 ]; then
  echo "O banco de dados está vazio. Verificando o arquivo SQL..."
  DUMP_FILE="/docker-entrypoint-initdb.d/bdm_digital_plugin.sql"

  if [ -f "$DUMP_FILE" ]; then
    echo "Importando o arquivo SQL..."
    if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$DUMP_FILE"; then
      echo "Erro ao importar o dump. Verifique o conteúdo do SQL."
      exit 1
    fi
  else
    echo "Arquivo $DUMP_FILE não encontrado. Abortando a importação."
  fi
else
  echo "Banco de dados já contém tabelas. Pulando importação."
fi

# Atualizar URLs do WordPress se necessário
if command -v wp >/dev/null 2>&1; then
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
else
  echo "WP-CLI não encontrado. Ignorando atualização de URLs."
fi
