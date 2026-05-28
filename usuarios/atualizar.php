<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
csrfValidarTokenPost();

exigirPerfil(["Admin", "SuperAdmin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioId = $_POST["UsuarioId"] ?? 0;
$nome = trim($_POST["Nome"] ?? "");
$email = trim($_POST["Email"] ?? "");
$senha = $_POST["Senha"] ?? "";
$perfil = $_POST["Perfil"] ?? "Atendente";
$ativo = $_POST["Ativo"] ?? 1;

if ($usuarioId <= 0) {
    die("Usuário inválido.");
}

if ($nome === "") {
    die("O campo Nome é obrigatório.");
}

if ($email === "") {
    die("O campo Email é obrigatório.");
}

if ($senha !== "" && strlen($senha) < 6) {
    die("A senha deve ter no mínimo 6 caracteres.");
}

$sqlVerifica = "
    SELECT COUNT(*) 
    FROM OS_Usuarios 
    WHERE Email = :Email
      AND UsuarioId <> :UsuarioId
      AND EmpresaId = :EmpresaId
";

$stmtVerifica = $conn->prepare($sqlVerifica);
$stmtVerifica->bindValue(":Email", $email);
$stmtVerifica->bindValue(":UsuarioId", $usuarioId, PDO::PARAM_INT);
$stmtVerifica->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtVerifica->execute();

$existe = $stmtVerifica->fetchColumn();

if ($existe > 0) {
    die("Já existe outro usuário cadastrado com este email.");
}

if ($senha !== "") {
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "
        UPDATE OS_Usuarios
        SET
            Nome = :Nome,
            Email = :Email,
            SenhaHash = :SenhaHash,
            Perfil = :Perfil,
            Ativo = :Ativo
        WHERE UsuarioId = :UsuarioId
          AND EmpresaId = :EmpresaId
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":SenhaHash", $senhaHash);
} else {
    $sql = "
        UPDATE OS_Usuarios
        SET
            Nome = :Nome,
            Email = :Email,
            Perfil = :Perfil,
            Ativo = :Ativo
        WHERE UsuarioId = :UsuarioId
          AND EmpresaId = :EmpresaId
    ";

    $stmt = $conn->prepare($sql);
}

$stmt->bindValue(":Nome", $nome);
$stmt->bindValue(":Email", $email);
$stmt->bindValue(":Perfil", $perfil);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);
$stmt->bindValue(":UsuarioId", $usuarioId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

if ((int)$usuarioId === (int)$_SESSION["UsuarioId"]) {
    $_SESSION["UsuarioNome"] = $nome;
    $_SESSION["UsuarioEmail"] = $email;
    $_SESSION["UsuarioPerfil"] = $perfil;

    if ((int)$ativo !== 1) {
        session_unset();
        session_destroy();
        header("Location: ../login.php");
        exit;
    }
}

header("Location: listar.php");
exit;