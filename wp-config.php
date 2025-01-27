<?php

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Database configuration
define('DB_NAME', getenv('WORDPRESS_DB_NAME'));
define('DB_USER', getenv('WORDPRESS_DB_USER'));
define('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD'));
define('DB_HOST', getenv('WORDPRESS_DB_HOST'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// WordPress URLs
define('WP_HOME', 'http://' . getenv('DOMAIN'));
define('WP_SITEURL', 'http://' . getenv('DOMAIN'));

// Authentication Unique Keys and Salts (ensure these are unique for your installation)
// You can generate these at https://api.wordpress.org/secret-key/1.1/salt/
define('AUTH_KEY', 'put your unique phrase here');
define('SECURE_AUTH_KEY', 'put your unique phrase here');
define('LOGGED_IN_KEY', 'put your unique phrase here');
define('NONCE_KEY', 'put your unique phrase here');
define('AUTH_SALT', 'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT', 'put your unique phrase here');
define('NONCE_SALT', 'put your unique phrase here');

// WordPress Database Table prefix
$table_prefix = 'wp_';

// Debug mode
define('WP_DEBUG', false);

// Absolute path to the WordPress directory.
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Sets up WordPress vars and included files.
require_once ABSPATH . 'wp-settings.php';
