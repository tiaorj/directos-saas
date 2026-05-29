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
if (!defined('IA_ATIVA')) {
    define('IA_ATIVA', false);
}

if (!defined('IA_PROVIDER')) {
    define('IA_PROVIDER', 'openai');
}

if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', 'sua_chave_openai_aqui');
}

if (!defined('OPENAI_MODEL')) {
    define('OPENAI_MODEL', 'modelo_a_configurar');
}