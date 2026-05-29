<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/config.php";
$baseUrl = rtrim(APP_URL, "/");

if (!isset($_SESSION["UsuarioId"])) {
    header("Location: " . $baseUrl . "/login.php");
    exit;
}

if (!isset($_SESSION["EmpresaId"]) || empty($_SESSION["EmpresaId"])) {
    session_destroy();
    header("Location: " . $baseUrl . "/login.php?erro=Empresa não vinculada ao usuário.");
    exit;
}

if (!isset($_SESSION["UsuarioPerfil"]) || empty($_SESSION["UsuarioPerfil"])) {
    session_destroy();
    header("Location: " . $baseUrl . "/login.php?erro=Perfil de usuário inválido.");
    exit;
}

/*
    Validação extra de segurança SaaS:
    Mesmo que o usuário já esteja logado, a empresa pode ser inativada
    pelo SuperAdmin. Por isso validamos o status da empresa em cada página protegida.
*/

if (!isset($conn)) {
    require_once __DIR__ . "/../config/conexao.php";
}

$empresaIdProtegida = (int)$_SESSION["EmpresaId"];

$sqlEmpresaProtegida = "
    SELECT 
        Ativo,
        NomeFantasia
    FROM OS_Empresas
    WHERE EmpresaId = :EmpresaId
";

$stmtEmpresaProtegida = $conn->prepare($sqlEmpresaProtegida);
$stmtEmpresaProtegida->bindValue(":EmpresaId", $empresaIdProtegida, PDO::PARAM_INT);
$stmtEmpresaProtegida->execute();

$empresaProtegida = $stmtEmpresaProtegida->fetch(PDO::FETCH_ASSOC);

if (!$empresaProtegida) {
    session_destroy();
    header("Location: " . $baseUrl . "/login.php?erro=Empresa não encontrada.");
    exit;
}

if ((int)$empresaProtegida["Ativo"] !== 1) {
    session_destroy();
    header("Location: " . $baseUrl . "/login.php?erro=Empresa inativa. Entre em contato com o suporte.");
    exit;
}

$_SESSION["EmpresaNome"] = $empresaProtegida["NomeFantasia"] ?? "";