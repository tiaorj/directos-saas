<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
csrfValidarTokenPost();

exigirPerfil(["Admin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$campoId = (int)($_POST["CampoId"] ?? 0);
$rotulo = trim($_POST["Rotulo"] ?? "");
$nomeCampo = trim($_POST["NomeCampo"] ?? "");
$tipoCampo = trim($_POST["TipoCampo"] ?? "texto");
$obrigatorio = isset($_POST["Obrigatorio"]) ? 1 : 0;
$ordem = (int)($_POST["Ordem"] ?? 0);
$ativo = (int)($_POST["Ativo"] ?? 1);

if ($campoId <= 0) {
    die("Campo inválido.");
}

if ($rotulo === "") {
    die("Rótulo é obrigatório.");
}

if ($nomeCampo === "") {
    die("Nome técnico é obrigatório.");
}

$nomeCampo = strtolower($nomeCampo);
$nomeCampo = preg_replace('/[^a-z0-9_]/', '_', $nomeCampo);
$nomeCampo = preg_replace('/_+/', '_', $nomeCampo);
$nomeCampo = trim($nomeCampo, '_');

if ($nomeCampo === "") {
    die("Nome técnico inválido.");
}

$tiposPermitidos = ["texto", "numero", "data", "textarea"];

if (!in_array($tipoCampo, $tiposPermitidos, true)) {
    die("Tipo de campo inválido.");
}

$sqlExiste = "
    SELECT COUNT(*) 
    FROM OS_CamposPersonalizados
    WHERE EmpresaId = :EmpresaId
      AND NomeCampo = :NomeCampo
      AND CampoId <> :CampoId
";

$stmtExiste = $conn->prepare($sqlExiste);
$stmtExiste->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtExiste->bindValue(":NomeCampo", $nomeCampo);
$stmtExiste->bindValue(":CampoId", $campoId, PDO::PARAM_INT);
$stmtExiste->execute();

if ((int)$stmtExiste->fetchColumn() > 0) {
    die("Já existe outro campo com este nome técnico.");
}

$sql = "
    UPDATE OS_CamposPersonalizados
    SET
        NomeCampo = :NomeCampo,
        Rotulo = :Rotulo,
        TipoCampo = :TipoCampo,
        Obrigatorio = :Obrigatorio,
        Ordem = :Ordem,
        Ativo = :Ativo
    WHERE CampoId = :CampoId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":NomeCampo", $nomeCampo);
$stmt->bindValue(":Rotulo", $rotulo);
$stmt->bindValue(":TipoCampo", $tipoCampo);
$stmt->bindValue(":Obrigatorio", $obrigatorio, PDO::PARAM_INT);
$stmt->bindValue(":Ordem", $ordem, PDO::PARAM_INT);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);
$stmt->bindValue(":CampoId", $campoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;