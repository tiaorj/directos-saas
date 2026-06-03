<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";

bloquearAcaoDemo();
csrfValidarTokenGet();

$empresaId = (int)$_SESSION["EmpresaId"];

$id = $_GET["id"] ?? 0;

exigirClienteDaEmpresa($conn, $id);

if ($id <= 0) {
    die("Cliente inválido.");
}

$sql = "
    UPDATE OS_Clientes
    SET Ativo = 0
    WHERE ClienteId = :ClienteId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ClienteId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    die("Cliente não encontrado para esta empresa.");
}

header("Location: listar.php");
exit;