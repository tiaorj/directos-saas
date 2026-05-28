<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$acao = $_GET["acao"] ?? "";
$origem = $_GET["origem"] ?? "dashboard";

if ($acao !== "ocultar" && $acao !== "exibir") {
    header("Location: ../dashboard.php");
    exit;
}

$ocultar = $acao === "ocultar" ? 1 : 0;

$sql = "
    UPDATE OS_Empresas
    SET OcultarOnboarding = :OcultarOnboarding
    WHERE EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OcultarOnboarding", $ocultar, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

if ($origem === "empresa") {
    header("Location: editar.php");
    exit;
}

header("Location: ../dashboard.php");
exit;