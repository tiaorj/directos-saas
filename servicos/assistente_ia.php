<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";
require_once "../config/config.php";

header("Content-Type: application/json; charset=utf-8");

csrfValidarTokenPost();

exigirPerfil(["Admin"]);

$tipoIA = $_POST["TipoIA"] ?? "";
$nome = trim($_POST["Nome"] ?? "");
$descricaoAtual = trim($_POST["Descricao"] ?? "");

if ($tipoIA !== "descricao") {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Tipo de IA inválido."
    ]);
    exit;
}

if ($nome === "") {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Informe o nome do serviço antes de usar a IA."
    ]);
    exit;
}

if (!defined("OPENAI_API_KEY") || trim(OPENAI_API_KEY) === "") {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "IA não está configurada neste ambiente."
    ]);
    exit;
}

$prompt = "
Você é um assistente para um sistema de ordem de serviço chamado DirectOS.

Crie uma descrição profissional, clara e comercial para o serviço abaixo.

Regras:
- Escreva em português do Brasil.
- Use linguagem simples e profissional.
- Não prometa garantia, prazo fixo ou resultado absoluto.
- Não use markdown.
- Não use emojis.
- Faça um texto curto, entre 2 e 4 frases.
- O texto deve servir para cadastro de serviço em assistência técnica/prestador de serviço.

Nome do serviço:
{$nome}

Descrição atual, se houver:
{$descricaoAtual}
";

$payload = [
    "model" => defined("OPENAI_MODEL") ? OPENAI_MODEL : "gpt-4o-mini",
    "messages" => [
        [
            "role" => "system",
            "content" => "Você ajuda a criar textos profissionais para cadastros de serviços em um sistema SaaS de ordem de serviço."
        ],
        [
            "role" => "user",
            "content" => $prompt
        ]
    ],
    "temperature" => 0.4,
    "max_tokens" => 250
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . OPENAI_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 40
]);

$resposta = curl_exec($ch);
$erroCurl = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($resposta === false) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao conectar com a IA: " . $erroCurl
    ]);
    exit;
}

$dados = json_decode($resposta, true);

if ($httpCode < 200 || $httpCode >= 300) {
    $mensagemErro = $dados["error"]["message"] ?? "Erro desconhecido ao chamar IA.";

    echo json_encode([
        "sucesso" => false,
        "mensagem" => $mensagemErro
    ]);
    exit;
}

$conteudo = trim($dados["choices"][0]["message"]["content"] ?? "");

if ($conteudo === "") {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "A IA não retornou conteúdo."
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "tipo" => "descricao",
    "conteudo" => $conteudo
]);
exit;