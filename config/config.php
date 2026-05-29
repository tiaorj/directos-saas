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
