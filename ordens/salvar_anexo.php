<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/arquivos.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
csrfValidarTokenPost();

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioId = (int)$_SESSION["UsuarioId"];
$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$visivelCliente = isset($_POST["VisivelCliente"]) ? 1 : 0;

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

exigirOrdemDaEmpresa($conn, $ordemServicoId);

$sqlOrdem = "
    SELECT OrdemServicoId
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId
      AND EmpresaId = :EmpresaId
";

$stmtOrdem = $conn->prepare($sqlOrdem);
$stmtOrdem->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmtOrdem->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtOrdem->execute();

$ordem = $stmtOrdem->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}

if (!isset($_FILES["Arquivo"]) || $_FILES["Arquivo"]["error"] !== UPLOAD_ERR_OK) {
    header("Location: anexar.php?id=" . $ordemServicoId . "&erro=Erro ao enviar arquivo.");
    exit;
}

$arquivo = $_FILES["Arquivo"];

$nomeOriginal = $arquivo["name"];
$tipoArquivo = $arquivo["type"];
$tamanhoBytes = (int)$arquivo["size"];
$tmpName = $arquivo["tmp_name"];

$extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

$extensoesPermitidas = [
    "jpg",
    "jpeg",
    "png",
    "gif",
    "pdf",
    "doc",
    "docx",
    "xls",
    "xlsx"
];

if (!in_array($extensao, $extensoesPermitidas)) {
    header("Location: anexar.php?id=" . $ordemServicoId . "&erro=Tipo de arquivo não permitido.");
    exit;
}

$tamanhoMaximo = 5 * 1024 * 1024;

if ($tamanhoBytes > $tamanhoMaximo) {
    header("Location: anexar.php?id=" . $ordemServicoId . "&erro=Arquivo maior que 5MB.");
    exit;
}

$pastaOS = diretorioUploadOs($empresaId, $ordemServicoId);

if (!is_dir($pastaOS)) {
    mkdir($pastaOS, 0777, true);
}

$nomeArquivo = uniqid("anexo_", true) . "." . $extensao;
$caminhoFisico = $pastaOS . DIRECTORY_SEPARATOR . $nomeArquivo;
$caminhoRelativo = "uploads/os/" . $empresaId . "/" . $ordemServicoId . "/" . $nomeArquivo;

if (!move_uploaded_file($tmpName, $caminhoFisico)) {
    header("Location: anexar.php?id=" . $ordemServicoId . "&erro=Não foi possível salvar o arquivo.");
    exit;
}

$sql = "
    INSERT INTO OS_OrdensServicoAnexos (
        OrdemServicoId,
        EmpresaId,
        UsuarioId,
        NomeOriginal,
        NomeArquivo,
        CaminhoArquivo,
        TipoArquivo,
        TamanhoBytes,
        VisivelCliente
    )
    VALUES (
        :OrdemServicoId,
        :EmpresaId,
        :UsuarioId,
        :NomeOriginal,
        :NomeArquivo,
        :CaminhoArquivo,
        :TipoArquivo,
        :TamanhoBytes,
        :VisivelCliente
    )
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->bindValue(":UsuarioId", $usuarioId, PDO::PARAM_INT);
$stmt->bindValue(":NomeOriginal", $nomeOriginal);
$stmt->bindValue(":NomeArquivo", $nomeArquivo);
$stmt->bindValue(":CaminhoArquivo", $caminhoRelativo);
$stmt->bindValue(":TipoArquivo", $tipoArquivo);
$stmt->bindValue(":TamanhoBytes", $tamanhoBytes, PDO::PARAM_INT);
$stmt->bindValue(":VisivelCliente", $visivelCliente, PDO::PARAM_INT);
$stmt->execute();

header("Location: visualizar.php?id=" . $ordemServicoId);
exit;
