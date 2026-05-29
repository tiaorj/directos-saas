<?php
require_once "../config/conexao.php";
require_once "../includes/arquivos.php";

$anexoId = (int)($_GET["id"] ?? 0);
$token = $_GET["token"] ?? "";

if ($anexoId <= 0 || $token === "") {
    die("Link de anexo inválido.");
}

$sql = "
    SELECT
        a.AnexoId,
        a.NomeOriginal,
        a.CaminhoArquivo,
        a.TipoArquivo,
        a.VisivelCliente,
        os.OrdemServicoId,
        os.TokenAcompanhamento
    FROM OS_OrdensServicoAnexos a
    INNER JOIN OS_OrdensServico os ON os.OrdemServicoId = a.OrdemServicoId AND os.EmpresaId = a.EmpresaId
    WHERE a.AnexoId = :AnexoId
      AND os.TokenAcompanhamento = :Token
      AND a.VisivelCliente = 1
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":AnexoId", $anexoId, PDO::PARAM_INT);
$stmt->bindValue(":Token", $token);
$stmt->execute();

$anexo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anexo) {
    die("Anexo não encontrado ou não liberado para visualização.");
}

$caminhoFisico = caminhoUploadFisico($anexo["CaminhoArquivo"]);

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
