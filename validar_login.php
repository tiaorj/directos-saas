<?php
session_start();

require_once "config/conexao.php";
require_once "includes/csrf.php";
csrfValidarTokenPost();

$email = trim($_POST["Email"] ?? "");
$senha = $_POST["Senha"] ?? "";

if ($email === "" || $senha === "") {
    header("Location: login.php?erro=Informe email e senha.");
    exit;
}

$sql = "
    SELECT 
        u.UsuarioId,
        u.EmpresaId,
        u.Nome,
        u.Email,
        u.SenhaHash,
        u.Perfil,
        u.Ativo AS UsuarioAtivo,

        e.NomeFantasia AS EmpresaNome,
        e.Ativo AS EmpresaAtiva
    FROM OS_Usuarios u
    INNER JOIN OS_Empresas e ON e.EmpresaId = u.EmpresaId
    WHERE u.Email = :Email
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Email", $email);
$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: login.php?erro=Usuário ou senha inválidos.");
    exit;
}

if ((int)$usuario["UsuarioAtivo"] !== 1) {
    header("Location: login.php?erro=Usuário inativo.");
    exit;
}

if ((int)$usuario["EmpresaAtiva"] !== 1) {
    header("Location: login.php?erro=Empresa inativa. Entre em contato com o suporte.");
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
$_SESSION["EmpresaId"] = $usuario["EmpresaId"];
$_SESSION["EmpresaNome"] = $usuario["EmpresaNome"];

header("Location: dashboard.php");
exit;