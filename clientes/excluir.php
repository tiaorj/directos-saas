<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Cliente inválido.");
}

$sql = "
    UPDATE OS_Clientes
    SET Ativo = 0
    WHERE ClienteId = :ClienteId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ClienteId", $id, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;