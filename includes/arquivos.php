<?php

require_once __DIR__ . '/../config/config.php';

function caminhoUploadFisico($caminhoArquivo)
{
    $caminhoArquivo = trim((string)$caminhoArquivo);

    if ($caminhoArquivo === '') {
        return '';
    }

    $normalizado = str_replace('\\', '/', $caminhoArquivo);

    if (preg_match('/^([A-Za-z]:)?\//', $normalizado) === 1) {
        return str_replace('/', DIRECTORY_SEPARATOR, $normalizado);
    }

    if (str_starts_with($normalizado, 'uploads/')) {
        $relativoUpload = substr($normalizado, strlen('uploads/'));
        $uploadDirBase = realpath(UPLOAD_DIR) ?: UPLOAD_DIR;
        $uploadDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($uploadDirBase, '/\\'));

        return $uploadDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativoUpload);
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizado);
}

function diretorioUploadOs($empresaId, $ordemServicoId)
{
    global $conn;

    if (isset($conn) && $conn instanceof PDO) {
        require_once __DIR__ . '/planos.php';

        $validacao = empresaPodeUsarRecursoPlano($conn, (int)$empresaId, 'anexos');

        if (!$validacao['permitido']) {
            die($validacao['mensagem']);
        }
    }

    $uploadDirBase = realpath(UPLOAD_DIR) ?: UPLOAD_DIR;
    $uploadDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($uploadDirBase, '/\\'));

    return $uploadDir
        . DIRECTORY_SEPARATOR . 'os'
        . DIRECTORY_SEPARATOR . (int)$empresaId
        . DIRECTORY_SEPARATOR . (int)$ordemServicoId;
}
