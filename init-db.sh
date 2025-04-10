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

# Determine o ambiente e a URL de destino
echo "# Determine o ambiente e a URL de destino"

if [ "$ENVIRONMENT" == "local" ]; then
  TARGET_URL="http://localhost:8000/"
elif [ "$ENVIRONMENT" == "hml" ]; then
  TARGET_URL="http://54.207.73.19:8000/"
else
  echo "Ambiente desconhecido: $ENVIRONMENT"
  exit 1
fi

# Verificar se o domínio atual é diferente de 54.207.73.19:8000 antes de substituir
CURRENT_URL="http://$(wp option get home --allow-root)"
TARGET_URL="http://54.207.73.19:8000/"

echo "Domínio atual: $CURRENT_URL"
echo "Domínio de destino: $TARGET_URL"

if [ "$CURRENT_URL" != "$TARGET_URL" ]; then
  echo "O domínio atual ($CURRENT_URL) não corresponde ao domínio de destino ($TARGET_URL). Realizando substituição..."

  # Executar substituição de URL através de SQL
  echo "Atualizando URLs através de SQL..."

  # Substituir na tabela wp_options (siteurl e home)
  mysql -h "$WORDPRESS_DB_HOST" -u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" "$WORDPRESS_DB_NAME" -e \
    "UPDATE wp_options SET option_value = REPLACE(option_value, 'http://localhost:8000/', 'http://54.207.73.19:8000/') WHERE option_name IN ('siteurl', 'home');"

  # Substituir em wp_postmeta
  mysql -h "$WORDPRESS_DB_HOST" -u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" "$WORDPRESS_DB_NAME" -e \
    "UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, 'http://localhost:8000/', 'http://54.207.73.19:8000/') WHERE meta_value LIKE '%localhost:8000%';"

  # Substituir em wp_usermeta
  mysql -h "$WORDPRESS_DB_HOST" -u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" "$WORDPRESS_DB_NAME" -e \
    "UPDATE wp_usermeta SET meta_value = REPLACE(meta_value, 'http://localhost:8000/', 'http://54.207.73.19:8000/') WHERE meta_value LIKE '%localhost:8000%';"

  # Verificar URLs no banco de dados (debugging)
  echo "Verificando URLs alteradas..."
  mysql -h "$WORDPRESS_DB_HOST" -u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" "$WORDPRESS_DB_NAME" -e \
    "SELECT * FROM wp_postmeta WHERE meta_value LIKE '%54.207.73.19:8000%';"

  mysql -h "$WORDPRESS_DB_HOST" -u "$WORDPRESS_DB_USER" -p"$WORDPRESS_DB_PASSWORD" "$WORDPRESS_DB_NAME" -e \
    "SELECT * FROM wp_usermeta WHERE meta_value LIKE '%54.207.73.19:8000%';"

  # Limpar cache do WordPress, se houver
  echo "Limpando cache do WordPress..."
  wp cache flush --allow-root

else
  echo "O domínio já é o esperado ($CURRENT_URL). Nenhuma substituição necessária."
fi

echo "Processo concluído."
