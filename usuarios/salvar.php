<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$nome = trim($_POST["Nome"] ?? "");
$email = trim($_POST["Email"] ?? "");
$senha = $_POST["Senha"] ?? "";
$perfil = $_POST["Perfil"] ?? "Atendente";
$ativo = $_POST["Ativo"] ?? 1;

if ($nome === "") {
    die("O campo Nome é obrigatório.");
}

if ($email === "") {
    die("O campo Email é obrigatório.");
}

if (strlen($senha) < 6) {
    die("A senha deve ter no mínimo 6 caracteres.");
}

$sqlVerifica = "
    SELECT COUNT(*) 
    FROM OS_Usuarios 
    WHERE Email = :Email
";

$stmtVerifica = $conn->prepare($sqlVerifica);
$stmtVerifica->bindValue(":Email", $email);
$stmtVerifica->execute();

$existe = $stmtVerifica->fetchColumn();

if ($existe > 0) {
    die("Já existe um usuário cadastrado com este email.");
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$sql = "
    INSERT INTO OS_Usuarios
    (
        Nome,
        Email,
        SenhaHash,
        Perfil,
        Ativo
    )
    VALUES
    (
        :Nome,
        :Email,
        :SenhaHash,
        :Perfil,
        :Ativo
    )
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Nome", $nome);
$stmt->bindValue(":Email", $email);
$stmt->bindValue(":SenhaHash", $senhaHash);
$stmt->bindValue(":Perfil", $perfil);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);
$stmt->execute();

header("Location: listar.php");
exit;