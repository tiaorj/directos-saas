<?php

require_once __DIR__ . "/../config/config.php";

function iaEstaAtiva()
{
    return defined("IA_ATIVA") && IA_ATIVA === true;
}

function iaValidarConfiguracao()
{
    if (!iaEstaAtiva()) {
        throw new Exception("IA não está ativa neste ambiente.");
    }

    if (!defined("OPENAI_API_KEY") || trim(OPENAI_API_KEY) === "") {
        throw new Exception("OPENAI_API_KEY não configurada.");
    }

    if (!defined("OPENAI_MODEL") || trim(OPENAI_MODEL) === "") {
        throw new Exception("OPENAI_MODEL não configurado.");
    }
}

function iaExtrairTextoResposta($resposta)
{
    if (isset($resposta["output_text"]) && trim($resposta["output_text"]) !== "") {
        return trim($resposta["output_text"]);
    }

    if (!empty($resposta["output"]) && is_array($resposta["output"])) {
        $textos = [];

        foreach ($resposta["output"] as $item) {
            if (!empty($item["content"]) && is_array($item["content"])) {
                foreach ($item["content"] as $conteudo) {
                    if (isset($conteudo["text"])) {
                        $textos[] = $conteudo["text"];
                    }
                }
            }
        }

        if (count($textos) > 0) {
            return trim(implode("\n", $textos));
        }
    }

    return "";
}

function iaChamarOpenAI($systemPrompt, $userPrompt)
{
    iaValidarConfiguracao();

    $payload = [
        "model" => OPENAI_MODEL,
        "input" => [
            [
                "role" => "system",
                "content" => $systemPrompt
            ],
            [
                "role" => "user",
                "content" => $userPrompt
            ]
        ]
    ];

    $ch = curl_init(OPENAI_API_URL);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 45
    ]);

    $resultado = curl_exec($ch);
    $erroCurl = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($resultado === false || $erroCurl !== "") {
        throw new Exception("Erro ao chamar IA: " . $erroCurl);
    }

    $json = json_decode($resultado, true);

    if (!is_array($json)) {
        throw new Exception("Resposta inválida da IA.");
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $mensagem = $json["error"]["message"] ?? "Erro HTTP " . $httpCode . " ao chamar IA.";
        throw new Exception($mensagem);
    }

    $texto = iaExtrairTextoResposta($json);

    if ($texto === "") {
        throw new Exception("A IA não retornou texto.");
    }

    return $texto;
}

function iaGerarResumoOS($descricaoProblema, $contexto = [])
{
    $descricaoProblema = trim($descricaoProblema);

    if ($descricaoProblema === "") {
        throw new Exception("Informe uma descrição para gerar o resumo.");
    }

    $titulo = trim($contexto["Titulo"] ?? "");
    $servico = trim($contexto["Servico"] ?? "");
    $cliente = trim($contexto["Cliente"] ?? "");

    $systemPrompt = "
Você é um assistente especializado em ordens de serviço para prestadores de serviço.

Sua tarefa é transformar uma descrição simples em um resumo profissional, claro e objetivo para ser usado em uma OS.

Regras:
- Escreva em português do Brasil.
- Não invente informações técnicas que não estejam no texto.
- Não prometa prazo, garantia ou orçamento.
- Use linguagem profissional, mas simples.
- Mantenha entre 1 e 3 parágrafos curtos.
- Se houver risco, mencione que será necessária avaliação técnica.
";

    $userPrompt = "
Dados da OS:

Cliente: {$cliente}
Serviço: {$servico}
Título: {$titulo}

Descrição original:
{$descricaoProblema}

Gere um resumo profissional para esta ordem de serviço.
";

    return iaChamarOpenAI($systemPrompt, $userPrompt);
}