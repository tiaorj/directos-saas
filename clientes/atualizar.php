<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$clienteId = $_POST["ClienteId"] ?? 0;
$nome = trim($_POST["Nome"] ?? "");
$telefone = trim($_POST["Telefone"] ?? "");
$email = trim($_POST["Email"] ?? "");
$documento = trim($_POST["Documento"] ?? "");
$endereco = trim($_POST["Endereco"] ?? "");
$cidade = trim($_POST["Cidade"] ?? "");
$estado = strtoupper(trim($_POST["Estado"] ?? ""));
$ativo = $_POST["Ativo"] ?? 1;

exigirClienteDaEmpresa($conn, $clienteId);

if ($clienteId <= 0) {
    die("Cliente inválido.");
}

if ($nome === "") {
    die("O campo Nome é obrigatório.");
}

$sql = "
    UPDATE OS_Clientes
    SET
        Nome = :Nome,
        Telefone = :Telefone,
        Email = :Email,
        Documento = :Documento,
        Endereco = :Endereco,
        Cidade = :Cidade,
        Estado = :Estado,
        Ativo = :Ativo
    WHERE ClienteId = :ClienteId
      AND EmpresaId = :EmpresaId
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
$stmt->bindValue(":ClienteId", $clienteId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;