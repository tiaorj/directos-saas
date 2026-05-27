<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$anexoId = (int)($_GET["id"] ?? 0);

if ($anexoId <= 0) {
    die("Anexo inválido.");
}

$sql = "
    SELECT
        AnexoId,
        OrdemServicoId,
        VisivelCliente
    FROM OS_OrdensServicoAnexos
    WHERE AnexoId = :AnexoId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":AnexoId", $anexoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$anexo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anexo) {
    die("Anexo não encontrado.");
}

$novoStatus = (int)$anexo["VisivelCliente"] === 1 ? 0 : 1;

$sqlUpdate = "
    UPDATE OS_OrdensServicoAnexos
    SET VisivelCliente = :VisivelCliente
    WHERE AnexoId = :AnexoId
      AND EmpresaId = :EmpresaId
";

$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bindValue(":VisivelCliente", $novoStatus, PDO::PARAM_INT);
$stmtUpdate->bindValue(":AnexoId", $anexoId, PDO::PARAM_INT);
$stmtUpdate->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtUpdate->execute();

header("Location: visualizar.php?id=" . $anexo["OrdemServicoId"]);
exit;