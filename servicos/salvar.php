<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
exigirPerfil(["Admin"]);
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";

bloquearAcaoDemo();
csrfValidarTokenPost();

$empresaId = (int)$_SESSION["EmpresaId"];
$nome = trim($_POST["Nome"] ?? "");
$descricao = trim($_POST["Descricao"] ?? "");
$checklistPadrao = trim($_POST["ChecklistPadrao"] ?? "");
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
        ChecklistPadrao,
        ValorBase,
        Ativo,
        EmpresaId
    )
    VALUES
    (
        :Nome,
        :Descricao,
        :ChecklistPadrao,
        :ValorBase,
        :Ativo,
        :EmpresaId
    )
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(":Nome", $nome);
$stmt->bindValue(":Descricao", $descricao);
$stmt->bindValue(":ChecklistPadrao", $checklistPadrao);
$stmt->bindValue(":ValorBase", $valorBase, $valorBase === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);

$stmt->execute();

header("Location: listar.php");
exit;