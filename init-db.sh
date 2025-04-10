#!/bin/bash

echo "Executando init-db.sh para inicializar o banco de dados e configurar URLs..."

# Carregar variáveis do arquivo .env
if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

# Verificar se o banco de dados já existe
if ! mysql -h"$WORDPRESS_DB_HOST" -u"$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" -e "USE $WORDPRESS_DB_NAME"; then
  echo "Banco de dados '$WORDPRESS_DB_NAME' não existe. Criando..."
  mysql -h"$WORDPRESS_DB_HOST" -u"$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" -e "CREATE DATABASE $WORDPRESS_DB_NAME"
else
  echo "Banco de dados '$WORDPRESS_DB_NAME' já existe. Pulando criação."
fi

# Verifica se o banco está vazio
TABLE_COUNT=$(mysql -h "$WORDPRESS_DB_HOST" -u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$WORDPRESS_DB_NAME';" -s --skip-column-names)

if [ "$TABLE_COUNT" -eq 0 ]; then
  echo "O banco de dados está vazio. Verificando o arquivo SQL..."
  DUMP_FILE="/docker-entrypoint-initdb.d/bdm_digital_plugin.sql"

  if [ -f "$DUMP_FILE" ]; then
    echo "Importando o arquivo SQL..."
    if ! mysql -h "$WORDPRESS_DB_HOST" -u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" "$WORDPRESS_DB_NAME" < "$DUMP_FILE"; then
      echo "Erro ao importar o dump. Verifique o conteúdo do SQL."
      exit 1
    fi
  else
    echo "Arquivo $DUMP_FILE não encontrado. Abortando a importação."
  fi
else
  echo "Banco de dados já contém tabelas. Pulando importação."
fi

# Atualizar URLs do WordPress se SITE_URL estiver definido e for diferente do atual
if [ -n "$SITE_URL" ]; then
  CURRENT_SITEURL=$(wp option get siteurl --allow-root)
  CURRENT_HOME=$(wp option get home --allow-root)

  echo "Site URL atual no banco:"
  echo "siteurl = $CURRENT_SITEURL"
  echo "home    = $CURRENT_HOME"

  if [ "$CURRENT_SITEURL" != "$SITE_URL" ] || [ "$CURRENT_HOME" != "$SITE_URL" ]; then
    echo "Atualizando URLs do WordPress para $SITE_URL..."
    wp search-replace "$CURRENT_SITEURL" "$SITE_URL" --all-tables --precise --allow-root
  else
    echo "SITE_URL já está correto: $CURRENT_SITEURL"
  fi
else
  echo "Variável SITE_URL não definida. Pulando search-replace."
fi
