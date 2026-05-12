<?php
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Ordem de serviço inválida.");
}

$sql = "
    DELETE FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;