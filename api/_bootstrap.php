<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexao.php';

function directtiHeader(string $name): string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    foreach ($headers as $key => $value) {
        if (strtolower($key) === strtolower($name)) {
            return trim($value);
        }
    }

    return '';
}

function directtiJson(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function directtiBadRequest(string $message): void
{
    directtiJson(400, [
        'success' => false,
        'message' => $message
    ]);
}

function directtiUnauthorized(): void
{
    directtiJson(401, [
        'success' => false,
        'message' => 'Não autorizado.'
    ]);
}

$apiKey = directtiHeader('X-DirectTI-Key');

$expectedKey = getenv('DIRECTTI_API_KEY');

if (!$expectedKey) {
    $expectedKey = 'directti-dev-123';
}

if (!$apiKey || !hash_equals($expectedKey, $apiKey)) {
    directtiUnauthorized();
}