<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/historico.php";

exigirPerfil(["Admin"]);

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Ordem de serviço inválida.");
}

$sqlAtual = "
    SELECT Status
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId
";

$stmtAtual = $conn->prepare($sqlAtual);
$stmtAtual->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtAtual->execute();

$ordemAtual = $stmtAtual->fetch(PDO::FETCH_ASSOC);

if (!$ordemAtual) {
    die("Ordem de serviço não encontrada.");
}

$statusAnterior = $ordemAtual["Status"];
if ($statusAnterior === "Cancelada") {
    die("Ordem de serviço já está cancelada.");
}

$sql = "
    UPDATE OS_OrdensServico
    SET 
        Status = 'Cancelada',
        DataConclusao = NULL
    WHERE OrdemServicoId = :OrdemServicoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->execute();

registrarHistoricoOS(
    $conn,
    $id,
    $_SESSION["UsuarioId"],
    $statusAnterior,
    "Cancelada",
    "Ordem de serviço cancelada."
);

header("Location: listar.php");
exit;