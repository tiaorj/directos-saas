<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/csrf.php";
require_once "../includes/n8n.php";
require_once "../includes/auditoria.php";

exigirPerfil(["SuperAdmin"]);
csrfValidarTokenPost();

$telefoneTeste = trim($_POST["TelefoneTeste"] ?? "");
$mensagemTeste = trim($_POST["MensagemTeste"] ?? "");

try {
    if ($telefoneTeste === "") {
        throw new Exception("Informe o telefone de teste.");
    }

    if ($mensagemTeste === "") {
        throw new Exception("Informe a mensagem de teste.");
    }

    $telefoneNormalizado = normalizarTelefoneWhatsApp($telefoneTeste);

    if ($telefoneNormalizado === "") {
        throw new Exception("Telefone de teste inválido.");
    }

    $payload = [
        "origem" => "DirectOS",
        "evento" => "teste_integracao_n8n",
        "empresa" => [
            "id" => (int)($_SESSION["EmpresaId"] ?? 0),
            "nome" => $_SESSION["EmpresaNome"] ?? "DirectOS"
        ],
        "usuario" => [
            "id" => (int)($_SESSION["UsuarioId"] ?? 0),
            "nome" => $_SESSION["UsuarioNome"] ?? "",
            "email" => $_SESSION["UsuarioEmail"] ?? ""
        ],
        "cliente" => [
            "id" => 0,
            "nome" => "Teste de integração",
            "telefone" => $telefoneNormalizado
        ],
        "os" => [
            "id" => 0,
            "codigo" => "TESTE-N8N",
            "titulo" => "Teste de integração n8n",
            "status" => "Teste",
            "servico" => "Integração",
            "link_publico" => rtrim(APP_URL, "/")
        ],
        "whatsapp" => [
            "telefone" => $telefoneNormalizado,
            "mensagem" => $mensagemTeste
        ],
        "data_envio" => date("c")
    ];

    n8nEnviarWhatsApp($payload);

    registrarAuditoria(
        $conn,
        "N8N_TESTE_WHATSAPP",
        "Integracoes",
        null,
        "Mensagem de teste enviada para o n8n."
    );

    header("Location: integracoes.php?sucesso=" . urlencode("Mensagem de teste enviada para o n8n com sucesso."));
    exit;

} catch (Exception $e) {
    header("Location: integracoes.php?erro=" . urlencode($e->getMessage()));
    exit;
}