<?php

$localConfig = __DIR__ . '/config.local.php';

if (file_exists($localConfig)) {
    require_once $localConfig;
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'DirectOS');
}

if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'local');
}

if (!defined('APP_URL')) {
    define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080/sistema-os-php-sqlserver');
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', APP_ENV !== 'producao');
}

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', getenv('UPLOAD_DIR') ?: __DIR__ . '/../uploads');
}

if (!defined('LOG_DIR')) {
    define('LOG_DIR', getenv('LOG_DIR') ?: __DIR__ . '/../logs');
}

if (!defined('PUBLIC_UPLOAD_MAX_SIZE_MB')) {
    define('PUBLIC_UPLOAD_MAX_SIZE_MB', 10);
}

if (!defined('IA_ATIVA')) {
    define('IA_ATIVA', getenv('IA_ATIVA') === 'true');
}

if (!defined('IA_PROVIDER')) {
    define('IA_PROVIDER', getenv('IA_PROVIDER') ?: 'openai');
}

if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
}

if (!defined('OPENAI_MODEL')) {
    define('OPENAI_MODEL', getenv('OPENAI_MODEL') ?: '');
}

if (!defined('OPENAI_API_URL')) {
    define('OPENAI_API_URL', getenv('OPENAI_API_URL') ?: 'https://api.openai.com/v1/responses');
}

if (!defined('N8N_ATIVO')) {
    define('N8N_ATIVO', getenv('N8N_ATIVO') === 'true');
}

if (!defined('N8N_WEBHOOK_WHATSAPP_URL')) {
    define('N8N_WEBHOOK_WHATSAPP_URL', getenv('N8N_WEBHOOK_WHATSAPP_URL') ?: '');
}

if (!defined('N8N_WEBHOOK_SECRET')) {
    define('N8N_WEBHOOK_SECRET', getenv('N8N_WEBHOOK_SECRET') ?: '');
}