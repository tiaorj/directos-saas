<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/planos.php";
csrfValidarTokenGet();

$empresaId = (int)$_SESSION["EmpresaId"];
$anexoId = (int)($_GET["id"] ?? 0);

exigirAnexoDaEmpresa($conn, $anexoId);

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

if ($novoStatus === 1) {
    $validacaoAnexos = empresaPodeUsarRecursoPlano($conn, $empresaId, "anexos");
    $validacaoAreaCliente = empresaPodeUsarRecursoPlano($conn, $empresaId, "area_cliente");

    if (!$validacaoAnexos["permitido"] || !$validacaoAreaCliente["permitido"]) {
        $mensagem = !$validacaoAnexos["permitido"] ? $validacaoAnexos["mensagem"] : $validacaoAreaCliente["mensagem"];
        header("Location: visualizar.php?id=" . $anexo["OrdemServicoId"] . "&mensagem=" . urlencode($mensagem));
        exit;
    }
}

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
