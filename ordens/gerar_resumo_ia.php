<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/ia.php";
require_once "../includes/auditoria.php";

header("Content-Type: application/json; charset=utf-8");

exigirPerfil(["Admin", "Atendente", "Tecnico", "SuperAdmin"]);
csrfValidarTokenPost();

$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$descricaoProblema = trim($_POST["DescricaoProblema"] ?? "");
$titulo = trim($_POST["Titulo"] ?? "");
$servico = trim($_POST["Servico"] ?? "");
$cliente = trim($_POST["Cliente"] ?? "");

try {
    if ($ordemServicoId > 0) {
        exigirOrdemDaEmpresa($conn, $ordemServicoId);
    }

    $resumo = iaGerarResumoOS($descricaoProblema, [
        "Titulo" => $titulo,
        "Servico" => $servico,
        "Cliente" => $cliente
    ]);

    registrarAuditoria(
        $conn,
        "IA_RESUMO_OS",
        "OS_OrdensServico",
        $ordemServicoId > 0 ? $ordemServicoId : null,
        "Resumo profissional da OS gerado com IA."
    );

    echo json_encode([
        "sucesso" => true,
        "resumo" => $resumo
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}