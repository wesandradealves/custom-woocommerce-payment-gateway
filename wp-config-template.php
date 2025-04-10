<?php

define('DB_NAME', getenv('WORDPRESS_DB_NAME'));
define('DB_USER', getenv('WORDPRESS_DB_USER'));
define('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD'));
define('DB_HOST', getenv('WORDPRESS_DB_HOST'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

$table_prefix = 'wp_';

// JWT e configurações adicionais
define('JWT_AUTH_SECRET_KEY', getenv('JWT_AUTH_SECRET_KEY'));
define('JWT_AUTH_CORS_ENABLE', true);
define('FS_METHOD', 'direct');

define('WP_DEBUG', getenv('WP_DEBUG'));
define('WP_DEBUG_DISPLAY', getenv('WP_DEBUG_DISPLAY'));
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
