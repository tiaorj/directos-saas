<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";
require_once "../includes/mensagens_whatsapp.php";

csrfValidarTokenPost();

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioId = (int)($_SESSION["UsuarioId"] ?? 0);
$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$telefone = trim($_POST["Telefone"] ?? "");
$mensagem = trim($_POST["Mensagem"] ?? "");

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

if ($mensagem === "") {
    die("Mensagem é obrigatória.");
}

exigirOrdemDaEmpresa($conn, $ordemServicoId);

$telefoneNormalizado = preg_replace('/\D/', '', $telefone);

if ($telefoneNormalizado !== "" && (strlen($telefoneNormalizado) === 10 || strlen($telefoneNormalizado) === 11)) {
    $telefoneNormalizado = "55" . $telefoneNormalizado;
}

try {
    registrarMensagemWhatsAppOS(
        $conn,
        $empresaId,
        $ordemServicoId,
        $usuarioId,
        "MENSAGEM_MANUAL",
        "MANUAL",
        $telefoneNormalizado,
        $mensagem
    );

    registrarAuditoria(
        $conn,
        "WHATSAPP_MANUAL_OS_NOVA_MENSAGEM",
        "OS_OrdensServico",
        $ordemServicoId,
        "Nova mensagem manual de WhatsApp preparada para a OS."
    );

    $_SESSION["WhatsAppManualOS"] = [
        "OrdemServicoId" => $ordemServicoId,
        "Telefone" => $telefoneNormalizado,
        "Mensagem" => $mensagem
    ];

    header("Location: visualizar.php?id=" . $ordemServicoId . "&mensagem=" . urlencode("Mensagem WhatsApp preparada com sucesso."));
    exit;

} catch (Exception $e) {
    header("Location: visualizar.php?id=" . $ordemServicoId . "&mensagem=" . urlencode("Erro ao preparar mensagem WhatsApp: " . $e->getMessage()));
    exit;
}