<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";

exigirPerfil(["Admin"]);

$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Ordem de serviço inválida.");
}

$sql = "
    UPDATE OS_OrdensServico
    SET 
        Status = 'Cancelada',
        DataConclusao = NULL
    WHERE OrdemServicoId = :OrdemServicoId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;