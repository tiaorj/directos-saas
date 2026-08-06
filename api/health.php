<?php
header('Content-Type: application/json; charset=utf-8');

$headers = getallheaders();

$apiKey = $headers['X-DirectTI-Key'] ?? '';
$expectedKey = 'directti-dev-123';

if (!hash_equals($expectedKey, $apiKey)) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Não autorizado'
    ]);

    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'DirectOS API online',
    'system' => 'DirectOS PHP',
    'version' => '1.0.0'
]);