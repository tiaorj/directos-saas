<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
csrfValidarTokenGet();

exigirPerfil(["SuperAdmin"]);

$empresaId = (int)($_GET["id"] ?? 0);
$acao = $_GET["acao"] ?? "";

if ($empresaId <= 0) {
    header("Location: empresas.php?erro=Empresa inválida.");
    exit;
}

if ($acao !== "ativar" && $acao !== "inativar") {
    header("Location: empresa.php?id=" . $empresaId . "&erro=Ação inválida.");
    exit;
}

$empresaIdSessao = (int)$_SESSION["EmpresaId"];

if ($acao === "inativar" && $empresaId === $empresaIdSessao) {
    header("Location: empresa.php?id=" . $empresaId . "&erro=Você não pode inativar a própria empresa do usuário logado.");
    exit;
}

$ativo = $acao === "ativar" ? 1 : 0;

$sql = "
    UPDATE OS_Empresas
    SET Ativo = :Ativo
    WHERE EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$mensagem = $ativo === 1 ? "Empresa ativada com sucesso." : "Empresa inativada com sucesso.";

header("Location: empresa.php?id=" . $empresaId . "&sucesso=" . urlencode($mensagem));
exit;