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
        os.TokenAcompanhamento,
        p.PermiteAnexos,
        p.PermiteAreaCliente
    FROM OS_OrdensServicoAnexos a
    INNER JOIN OS_OrdensServico os ON os.OrdemServicoId = a.OrdemServicoId AND os.EmpresaId = a.EmpresaId
    INNER JOIN OS_Empresas e ON e.EmpresaId = os.EmpresaId
    INNER JOIN OS_Planos p ON p.PlanoId = e.PlanoId
    WHERE a.AnexoId = :AnexoId
      AND os.TokenAcompanhamento = :Token
      AND a.VisivelCliente = 1
      AND e.Ativo = 1
      AND p.Ativo = 1
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":AnexoId", $anexoId, PDO::PARAM_INT);
$stmt->bindValue(":Token", $token);
$stmt->execute();

$anexo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anexo) {
    die("Anexo não encontrado ou não liberado para visualização.");
}

if ((int)($anexo["PermiteAreaCliente"] ?? 0) !== 1 || (int)($anexo["PermiteAnexos"] ?? 0) !== 1) {
    die("Este recurso não está disponível no plano atual da empresa.");
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
