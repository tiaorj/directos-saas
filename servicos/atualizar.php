<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$servicoId = $_POST["ServicoId"] ?? 0;
$nome = trim($_POST["Nome"] ?? "");
$descricao = trim($_POST["Descricao"] ?? "");
$valorBase = $_POST["ValorBase"] !== "" ? $_POST["ValorBase"] : null;
$ativo = $_POST["Ativo"] ?? 1;

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
    WHERE ServicoId = :ServicoId
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(":Nome", $nome);
$stmt->bindValue(":Descricao", $descricao);
$stmt->bindValue(":ValorBase", $valorBase, $valorBase === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);
$stmt->bindValue(":ServicoId", $servicoId, PDO::PARAM_INT);

$stmt->execute();

header("Location: listar.php");
exit;