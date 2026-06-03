<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
csrfValidarTokenGet();

exigirPerfil(["Admin", "SuperAdmin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    die("Usuário inválido.");
}

if ((int)$id === (int)$_SESSION["UsuarioId"]) {
    die("Você não pode inativar o próprio usuário logado.");
}

exigirUsuarioDaEmpresa($conn, $id);

$sql = "
    UPDATE OS_Usuarios
    SET Ativo = 0
    WHERE UsuarioId = :UsuarioId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":UsuarioId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

registrarAuditoria(
    $conn,
    "EXCLUSAO_USUARIO",
    "OS_Usuarios",
    $id,
    "Usuário excluído.",
    $empresaId,
    $_SESSION["UsuarioId"]
);

header("Location: listar.php");
exit;
