<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
csrfValidarTokenGet();

$empresaId = (int)$_SESSION["EmpresaId"];

$id = $_GET["id"] ?? 0;

exigirServicoDaEmpresa($conn, $id);

if ($id <= 0) {
    die("Serviço inválido.");
}

$sql = "
    UPDATE OS_Servicos
    SET Ativo = 0
    WHERE ServicoId = :ServicoId AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":ServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    die("Serviço não encontrado para esta empresa.");
}

header("Location: listar.php");
exit;