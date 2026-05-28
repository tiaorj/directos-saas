<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/historico.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
csrfValidarTokenPost();

exigirPerfil(["Admin", "Atendente", "Tecnico"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$status = $_POST["Status"] ?? "Aberta";
$descricaoSolucao = trim($_POST["DescricaoSolucao"] ?? "");
$observacao = trim($_POST["Observacao"] ?? "");
$usuarioId = $_SESSION["UsuarioId"];


if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

exigirOrdemDaEmpresa($conn, $ordemServicoId);

$sqlAtual = "
    SELECT 
        Status, 
        DataConclusao,
        DescricaoSolucao,
        Observacao
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId AND EmpresaId = :EmpresaId
";

$stmtAtual = $conn->prepare($sqlAtual);
$stmtAtual->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmtAtual->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtAtual->execute();

$ordemAtual = $stmtAtual->fetch(PDO::FETCH_ASSOC);

if (!$ordemAtual) {
    die("Ordem de serviço não encontrada.");
}

$statusAnterior = $ordemAtual["Status"];
$dataConclusao = $ordemAtual["DataConclusao"];

if ($status === "Concluída" && empty($dataConclusao)) {
    $dataConclusao = date("Y-m-d H:i:s");
}

if ($status !== "Concluída") {
    $dataConclusao = null;
}

$sql = "
    UPDATE OS_OrdensServico
    SET
        Status = :Status,
        DescricaoSolucao = :DescricaoSolucao,
        Observacao = :Observacao,
        DataConclusao = :DataConclusao
    WHERE OrdemServicoId = :OrdemServicoId AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Status", $status);
$stmt->bindValue(":DescricaoSolucao", $descricaoSolucao);
$stmt->bindValue(":Observacao", $observacao);
$stmt->bindValue(":DataConclusao", $dataConclusao, $dataConclusao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$descricaoHistorico = "Atendimento técnico atualizado.";

if ($statusAnterior !== $status) {
    $descricaoHistorico .= " Status alterado de '{$statusAnterior}' para '{$status}'.";
}

if (($ordemAtual["DescricaoSolucao"] ?? "") !== $descricaoSolucao) {
    $descricaoHistorico .= " Solução aplicada atualizada.";
}

if (($ordemAtual["Observacao"] ?? "") !== $observacao) {
    $descricaoHistorico .= " Observação atualizada.";
}

registrarHistoricoOS(
    $conn,
    $ordemServicoId,
    $usuarioId,
    $statusAnterior,
    $status,
    $descricaoHistorico
);

header("Location: visualizar.php?id=" . $ordemServicoId);
exit;
