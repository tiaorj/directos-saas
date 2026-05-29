<?php

require_once __DIR__ . "/../config/config.php";

function n8nEstaAtivo()
{
    return defined("N8N_ATIVO") && N8N_ATIVO === true;
}

function n8nValidarConfiguracaoWhatsApp()
{
    if (!n8nEstaAtivo()) {
        throw new Exception("Integração n8n não está ativa neste ambiente.");
    }

    if (!defined("N8N_WEBHOOK_WHATSAPP_URL") || trim(N8N_WEBHOOK_WHATSAPP_URL) === "") {
        throw new Exception("Webhook do n8n para WhatsApp não configurado.");
    }

    if (!defined("N8N_WEBHOOK_SECRET") || trim(N8N_WEBHOOK_SECRET) === "") {
        throw new Exception("Segredo do webhook n8n não configurado.");
    }
}

function n8nEnviarWhatsApp($payload)
{
    n8nValidarConfiguracaoWhatsApp();

    $ch = curl_init(N8N_WEBHOOK_WHATSAPP_URL);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-DirectOS-Secret: " . N8N_WEBHOOK_SECRET
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30
    ]);

    $resultado = curl_exec($ch);
    $erroCurl = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($resultado === false || $erroCurl !== "") {
        throw new Exception("Erro ao chamar n8n: " . $erroCurl);
    }

if ($httpCode < 200 || $httpCode >= 300) {
    $mensagemErro = "n8n retornou HTTP " . $httpCode . ".";

    if (!empty($resultado)) {
        $mensagemErro .= " Resposta: " . substr($resultado, 0, 500);
    }

    throw new Exception($mensagemErro);
}

    $json = json_decode($resultado, true);

    return [
        "httpCode" => $httpCode,
        "resposta" => is_array($json) ? $json : $resultado
    ];
}

function normalizarTelefoneWhatsApp($telefone)
{
    $telefone = preg_replace('/\D/', '', $telefone ?? '');

    if ($telefone === "") {
        return "";
    }

    if (strlen($telefone) === 10 || strlen($telefone) === 11) {
        $telefone = "55" . $telefone;
    }

    return $telefone;
}