<?php
session_start();

require_once "config/conexao.php";

$email = trim($_POST["Email"] ?? "");
$senha = $_POST["Senha"] ?? "";

if ($email === "" || $senha === "") {
    header("Location: login.php?erro=Informe email e senha.");
    exit;
}

$sql = "
    SELECT 
        UsuarioId,
        Nome,
        Email,
        SenhaHash,
        Perfil,
        Ativo
    FROM OS_Usuarios
    WHERE Email = :Email
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Email", $email);
$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: login.php?erro=Usuário ou senha inválidos.");
    exit;
}

if ((int)$usuario["Ativo"] !== 1) {
    header("Location: login.php?erro=Usuário inativo.");
    exit;
}

if (!password_verify($senha, $usuario["SenhaHash"])) {
    header("Location: login.php?erro=Usuário ou senha inválidos.");
    exit;
}

$_SESSION["UsuarioId"] = $usuario["UsuarioId"];
$_SESSION["UsuarioNome"] = $usuario["Nome"];
$_SESSION["UsuarioEmail"] = $usuario["Email"];
$_SESSION["UsuarioPerfil"] = $usuario["Perfil"];

header("Location: dashboard.php");
exit;