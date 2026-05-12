<?php
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Cliente inválido.");
}

$sql = "
    DELETE FROM OS_Clientes
    WHERE ClienteId = :ClienteId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ClienteId", $id, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;