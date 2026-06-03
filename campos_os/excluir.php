<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
csrfValidarTokenGet();

exigirPerfil(["Admin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$campoId = (int)($_GET["id"] ?? 0);

if ($campoId <= 0) {
    die("Campo inválido.");
}

$sql = "
    DELETE FROM OS_CamposPersonalizados
    WHERE CampoId = :CampoId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":CampoId", $campoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;