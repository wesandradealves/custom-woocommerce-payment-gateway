#!/bin/bash
set -x

# 📦 Carregar variáveis do .env
if [ -f /var/www/.env ]; then
  set -o allexport
  source /var/www/.env || echo "⚠️ Erro ao carregar .env"
  set +o allexport
fi

# ✅ Validar variáveis obrigatórias
required_vars=("WORDPRESS_DB_NAME" "WORDPRESS_DB_USER" "WORDPRESS_DB_PASSWORD" "WORDPRESS_DB_HOST")
for var in "${required_vars[@]}"; do
  if [ -z "${!var}" ]; then
    echo "❌ Variável $var não está definida no .env"
    exit 1
  fi
done

# ✅ Mostrar variáveis carregadas
echo "✅ WORDPRESS_DB_HOST=$WORDPRESS_DB_HOST"
echo "✅ WORDPRESS_DB_USER=$WORDPRESS_DB_USER"
echo "✅ WORDPRESS_DB_PASSWORD=$WORDPRESS_DB_PASSWORD"
echo "✅ WORDPRESS_DB_NAME=$WORDPRESS_DB_NAME"
echo "✅ JWT_AUTH_SECRET_KEY=$JWT_AUTH_SECRET_KEY"
echo "✅ SITE_URL=$SITE_URL"
echo "✅ WP_DEBUG=$WP_DEBUG"

WPCONFIG="/var/www/html/wp-config.php"
WPCONFIG_TEMPLATE="/var/www/html/wp-config-template.php"

# 📄 Criar wp-config.php se não existir
if [ ! -f "$WPCONFIG" ]; then
    echo "🛠️ Criando wp-config.php a partir do template..."
    cp "$WPCONFIG_TEMPLATE" "$WPCONFIG"
fi

# 🧽 Limpar ^M (CRLF) de Windows
echo "🧹 Limpando ^M do wp-config.php..."
sed -i 's/\r$//' "$WPCONFIG"

# 🔐 Gerar JWT_AUTH_SECRET_KEY se não estiver definido
if [ -z "$JWT_AUTH_SECRET_KEY" ]; then
  export JWT_AUTH_SECRET_KEY=$(openssl rand -base64 64)
  echo "🔐 JWT_AUTH_SECRET_KEY gerado dinamicamente"
else
  echo "🔐 JWT_AUTH_SECRET_KEY já existe, pulando..."
fi

# 🔁 Substituir getenv() pelos valores reais
sed -i "s/getenv('WORDPRESS_DB_NAME')/'${WORDPRESS_DB_NAME}'/" "$WPCONFIG"
sed -i "s/getenv('WORDPRESS_DB_USER')/'${WORDPRESS_DB_USER}'/" "$WPCONFIG"
sed -i "s/getenv('WORDPRESS_DB_PASSWORD')/'${WORDPRESS_DB_PASSWORD}'/" "$WPCONFIG"
sed -i "s/getenv('WORDPRESS_DB_HOST')/'${WORDPRESS_DB_HOST}'/" "$WPCONFIG"
sed -i "s/getenv('WP_DEBUG') === 'true'/true/" "$WPCONFIG"
sed -i "s/getenv('JWT_AUTH_SECRET_KEY')/'${JWT_AUTH_SECRET_KEY}'/" "$WPCONFIG"

# ➕ Adicionar define() se não existir
insert_define() {
  local key="$1"
  local value="$2"
  if ! grep -q "define('$key'," "$WPCONFIG"; then
    echo "➕ Adicionando define('$key', $value) ao wp-config.php"
    sed -i "/^\/\* That's all, stop editing! Happy publishing. \*\//i define('$key', $value);" "$WPCONFIG"
  else
    echo "✔️ $key já existe em wp-config.php, pulando..."
  fi
}

insert_define "FS_METHOD" "'direct'"
insert_define "JWT_AUTH_CORS_ENABLE" "true"

# 🕒 Aguardar MySQL estar pronto
echo "⏳ Aguardando MySQL estar pronto..."
until mysqladmin ping -h"$WORDPRESS_DB_HOST" --silent; do
  sleep 5
done
echo "✅ MySQL está pronto!"

# 🧪 Executar script de inicialização do banco de dados
if [ -f /usr/local/bin/init-db.sh ]; then
  echo "🗄️ Executando init-db.sh..."
  /bin/bash /usr/local/bin/init-db.sh
else
  echo "⚠️ init-db.sh não encontrado."
fi

# 🧰 Verificar instalação do WordPress
if ! wp core is-installed --allow-root; then
    echo "⚠️ WordPress ainda não está instalado. Instalando agora..."
    wp core install \
        --url="$SITE_URL" \
        --title="Meu Site WordPress" \
        --admin_user="$WORDPRESS_USER" \
        --admin_password="$WORDPRESS_PWD" \
        --admin_email="admin@example.com" \
        --allow-root
    echo "✔️ WordPress instalado com sucesso!"
else
    echo "✔️ WordPress já está instalado, pulando instalação."
fi

# 🔐 Permissões
chown www-data:www-data "$WPCONFIG"
chmod 664 "$WPCONFIG"

# 🔍 Mostrar últimas linhas do wp-config.php (debug)
echo "📄 Conteúdo final do wp-config.php:"
tail -n 20 "$WPCONFIG"

# 🚀 Inicializar Apache
echo "====================================="
echo "✅ Ambiente WordPress preparado!"
echo "====================================="
exec docker-entrypoint.sh apache2-foreground