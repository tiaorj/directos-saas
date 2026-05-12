<?php
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

$sql = "
    DELETE FROM FIN_Lancamentos
    WHERE LancamentoId = :LancamentoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":LancamentoId", $id, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;