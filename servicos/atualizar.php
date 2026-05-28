<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$servicoId = $_POST["ServicoId"] ?? 0;
$nome = trim($_POST["Nome"] ?? "");
$descricao = trim($_POST["Descricao"] ?? "");
$valorBase = $_POST["ValorBase"] !== "" ? $_POST["ValorBase"] : null;
$ativo = $_POST["Ativo"] ?? 1;

exigirServicoDaEmpresa($conn, $servicoId);

if ($servicoId <= 0) {
    die("Serviço inválido.");
}

if ($nome === "") {
    die("O campo Nome é obrigatório.");
}

$sql = "
    UPDATE OS_Servicos
    SET
        Nome = :Nome,
        Descricao = :Descricao,
        ValorBase = :ValorBase,
        Ativo = :Ativo
    WHERE ServicoId = :ServicoId AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(":Nome", $nome);
$stmt->bindValue(":Descricao", $descricao);
$stmt->bindValue(":ValorBase", $valorBase, $valorBase === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);
$stmt->bindValue(":ServicoId", $servicoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    die("Serviço não encontrado para esta empresa.");
}

header("Location: listar.php");
exit;