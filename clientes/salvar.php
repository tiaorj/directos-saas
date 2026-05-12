<?php
require_once "../config/conexao.php";

$nome = trim($_POST["Nome"] ?? "");
$telefone = trim($_POST["Telefone"] ?? "");
$email = trim($_POST["Email"] ?? "");
$documento = trim($_POST["Documento"] ?? "");
$endereco = trim($_POST["Endereco"] ?? "");
$cidade = trim($_POST["Cidade"] ?? "");
$estado = strtoupper(trim($_POST["Estado"] ?? ""));
$ativo = $_POST["Ativo"] ?? 1;

if ($nome === "") {
    die("O campo Nome é obrigatório.");
}

$sql = "
    INSERT INTO OS_Clientes
    (
        Nome,
        Telefone,
        Email,
        Documento,
        Endereco,
        Cidade,
        Estado,
        Ativo
    )
    VALUES
    (
        :Nome,
        :Telefone,
        :Email,
        :Documento,
        :Endereco,
        :Cidade,
        :Estado,
        :Ativo
    )
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(":Nome", $nome);
$stmt->bindValue(":Telefone", $telefone);
$stmt->bindValue(":Email", $email);
$stmt->bindValue(":Documento", $documento);
$stmt->bindValue(":Endereco", $endereco);
$stmt->bindValue(":Cidade", $cidade);
$stmt->bindValue(":Estado", $estado);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);

$stmt->execute();

header("Location: listar.php");
exit;