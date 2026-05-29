<?php

if (!function_exists('carregarEnv')) {
    function carregarEnv($caminho)
    {
        if (!file_exists($caminho)) {
            return false;
        }

        $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }

            [$chave, $valor] = explode('=', $linha, 2);

            $chave = trim($chave);
            $valor = trim($valor);
            $valor = trim($valor, "\"'");

            if ($chave === '') {
                continue;
            }

            if (getenv($chave) === false) {
                putenv($chave . '=' . $valor);
            }

            if (!array_key_exists($chave, $_ENV)) {
                $_ENV[$chave] = $valor;
            }

            if (!array_key_exists($chave, $_SERVER)) {
                $_SERVER[$chave] = $valor;
            }
        }

        return true;
    }
}

carregarEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/config.php';

$localConfig = __DIR__ . '/config.local.php';

if (file_exists($localConfig)) {
    require_once $localConfig;
}

$server = defined('DB_SERVER') ? DB_SERVER : getenv('DB_SERVER');
$database = defined('DB_DATABASE') ? DB_DATABASE : (getenv('DB_DATABASE') ?: getenv('DB_NAME'));
$username = defined('DB_USERNAME') ? DB_USERNAME : (getenv('DB_USERNAME') ?: getenv('DB_USER'));
$password = defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') ?: getenv('DB_PASS'));
$trustServerCertificate = defined('DB_TRUST_SERVER_CERTIFICATE')
    ? DB_TRUST_SERVER_CERTIFICATE
    : getenv('DB_TRUST_SERVER_CERTIFICATE');

if ($trustServerCertificate === false || $trustServerCertificate === '') {
    $trustServerCertificate = true;
}

$trustServerCertificate = filter_var($trustServerCertificate, FILTER_VALIDATE_BOOLEAN);

if (!$server || !$database || !$username) {
    die('Configuracao de banco de dados incompleta.');
}

try {
    $trust = $trustServerCertificate ? 'true' : 'false';

    $conn = new PDO(
        "sqlsrv:Server={$server};Database={$database};TrustServerCertificate={$trust}",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('Erro na conexao com SQL Server: ' . $e->getMessage());
    }

    die('Erro ao conectar ao banco de dados.');
}
