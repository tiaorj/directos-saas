<?php

function carregarEnv($caminho)
{
    if (!file_exists($caminho)) {
        die("Arquivo .env não encontrado.");
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        if ($linha === "" || str_starts_with($linha, "#")) {
            continue;
        }

        [$chave, $valor] = explode("=", $linha, 2);

        $_ENV[trim($chave)] = trim($valor);
    }
}

carregarEnv(__DIR__ . '/../.env');

$serverName = $_ENV['DB_SERVER'];
$database = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];

try{
    $conn = new PDO(
        "sqlsrv:server=$serverName;Database=$database",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("Erro na conexão com SQL Server: " . $e->getMessage());
};

