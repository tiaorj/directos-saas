<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/arquivos.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
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
        CaminhoArquivo
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

$caminhoFisico = caminhoUploadFisico($anexo["CaminhoArquivo"]);

if (file_exists($caminhoFisico)) {
    unlink($caminhoFisico);
}

$sqlDelete = "
    DELETE FROM OS_OrdensServicoAnexos
    WHERE AnexoId = :AnexoId
      AND EmpresaId = :EmpresaId
";

$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bindValue(":AnexoId", $anexoId, PDO::PARAM_INT);
$stmtDelete->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtDelete->execute();

registrarAuditoria(
    $conn,
    "EXCLUSAO_ANEXO_ORDEM_SERVICO",
    "OS_OrdensServicoAnexos",
    $anexoId,
    "Anexo excluído.",
    $empresaId,
    $_SESSION["UsuarioId"]
);

header("Location: visualizar.php?id=" . $anexo["OrdemServicoId"]);
exit;
