<?php

// Copie este arquivo para config.local.php e ajuste conforme seu ambiente.

if (!defined('APP_ENV')) {
    define('APP_ENV', 'local');
}

if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost:8080/sistema-os-php-sqlserver');
}

if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'localhost');
}

if (!defined('DB_DATABASE')) {
    define('DB_DATABASE', 'DirectOS');
}

if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', 'seu_usuario');
}

if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'sua_senha');
}

if (!defined('DB_TRUST_SERVER_CERTIFICATE')) {
    define('DB_TRUST_SERVER_CERTIFICATE', true);
}
