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
        EmpresaId,
        OrdemServicoId,
        NomeOriginal,
        CaminhoArquivo,
        TipoArquivo
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

$caminhoFisico = "../" . $anexo["CaminhoArquivo"];

if (!file_exists($caminhoFisico)) {
    die("Arquivo não encontrado.");
}

$tipoArquivo = $anexo["TipoArquivo"] ?: "application/octet-stream";
$nomeOriginal = $anexo["NomeOriginal"] ?: "anexo";

header("Content-Type: " . $tipoArquivo);
header("Content-Disposition: inline; filename=\"" . basename($nomeOriginal) . "\"");
header("Content-Length: " . filesize($caminhoFisico));

readfile($caminhoFisico);
exit;