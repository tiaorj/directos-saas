<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
csrfValidarTokenPost();

exigirPerfil(["Admin", "SuperAdmin"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$segmento = trim($_POST["Segmento"] ?? "");

$segmentosPermitidos = [
    "",
    "oficina",
    "informatica",
    "ar_condicionado",
    "eletronica",
    "servicos_gerais",
    "personalizado"
];

if (!in_array($segmento, $segmentosPermitidos, true)) {
    die("Segmento inválido.");
}

$sql = "
    UPDATE OS_Empresas
    SET Segmento = :Segmento
    WHERE EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Segmento", $segmento === "" ? null : $segmento, $segmento === "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

header("Location: segmento.php?mensagem=" . urlencode("Segmento atualizado com sucesso."));
exit;