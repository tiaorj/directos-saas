<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
require_once "../config/conexao.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$nome = trim($_POST["Nome"] ?? "");
$descricao = trim($_POST["Descricao"] ?? "");
$valorBase = $_POST["ValorBase"] !== "" ? $_POST["ValorBase"] : null;
$ativo = $_POST["Ativo"] ?? 1;

if ($nome === "") {
    die("O campo Nome é obrigatório.");
}

$sql = "
    INSERT INTO OS_Servicos
    (
        Nome,
        Descricao,
        ValorBase,
        Ativo,
        EmpresaId
    )
    VALUES
    (
        :Nome,
        :Descricao,
        :ValorBase,
        :Ativo,
        :EmpresaId
    )
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(":Nome", $nome);
$stmt->bindValue(":Descricao", $descricao);
$stmt->bindValue(":ValorBase", $valorBase, $valorBase === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);

$stmt->execute();

header("Location: listar.php");
exit;