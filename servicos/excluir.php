<?php
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Serviço inválido.");
}

$sql = "
    DELETE FROM OS_Servicos
    WHERE ServicoId = :ServicoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ServicoId", $id, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;