<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$ordemServicoId = $_POST["OrdemServicoId"] ?? 0;
$status = $_POST["Status"] ?? "Aberta";
$descricaoSolucao = trim($_POST["DescricaoSolucao"] ?? "");
$observacao = trim($_POST["Observacao"] ?? "");

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

$sqlAtual = "
    SELECT Status, DataConclusao
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId
";

$stmtAtual = $conn->prepare($sqlAtual);
$stmtAtual->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmtAtual->execute();

$ordemAtual = $stmtAtual->fetch(PDO::FETCH_ASSOC);

if (!$ordemAtual) {
    die("Ordem de serviço não encontrada.");
}

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
    WHERE OrdemServicoId = :OrdemServicoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Status", $status);
$stmt->bindValue(":DescricaoSolucao", $descricaoSolucao);
$stmt->bindValue(":Observacao", $observacao);
$stmt->bindValue(":DataConclusao", $dataConclusao, $dataConclusao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmt->execute();

header("Location: visualizar.php?id=" . $ordemServicoId);
exit;