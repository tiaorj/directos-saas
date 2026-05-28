<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["Admin", "SuperAdmin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$id = $_GET["id"] ?? 0;

if ($id <= 0) {
    die("Usuário inválido.");
}

if ((int)$id === (int)$_SESSION["UsuarioId"]) {
    die("Você não pode inativar o próprio usuário logado.");
}

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

header("Location: listar.php");
exit;