<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
require_once "../config/conexao.php";

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Serviço inválido.");
}

$sql = "
    UPDATE OS_Servicos
    SET Ativo = 0
    WHERE ServicoId = :ServicoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ServicoId", $id, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;