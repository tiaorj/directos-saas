<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
csrfValidarTokenPost();

$empresaIdSessao = (int)$_SESSION["EmpresaId"];
$empresaIdPost = (int)($_POST["EmpresaId"] ?? 0);

if ($empresaIdPost !== $empresaIdSessao) {
    die("Acesso negado.");
}

$nomeFantasia = trim($_POST["NomeFantasia"] ?? "");
$razaoSocial = trim($_POST["RazaoSocial"] ?? "");
$cnpj = trim($_POST["Cnpj"] ?? "");
$email = trim($_POST["Email"] ?? "");
$telefone = trim($_POST["Telefone"] ?? "");
$whatsApp = trim($_POST["WhatsApp"] ?? "");
$slug = trim($_POST["Slug"] ?? "");

if ($nomeFantasia === "") {
    header("Location: editar.php?erro=Nome fantasia é obrigatório.");
    exit;
}

if ($slug === "") {
    $slug = strtolower($nomeFantasia);
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');
}

$slug = strtolower($slug);
$slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
$slug = trim($slug, '-');

if ($slug === "") {
    header("Location: editar.php?erro=Slug inválido.");
    exit;
}

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: editar.php?erro=E-mail inválido.");
    exit;
}

$sqlSlug = "
    SELECT COUNT(*) AS Total
    FROM OS_Empresas
    WHERE Slug = :Slug
      AND EmpresaId <> :EmpresaId
";

$stmtSlug = $conn->prepare($sqlSlug);
$stmtSlug->bindValue(":Slug", $slug);
$stmtSlug->bindValue(":EmpresaId", $empresaIdSessao, PDO::PARAM_INT);
$stmtSlug->execute();

$slugExiste = $stmtSlug->fetch(PDO::FETCH_ASSOC);

if ((int)$slugExiste["Total"] > 0) {
    header("Location: editar.php?erro=Este slug já está sendo utilizado por outra empresa.");
    exit;
}

$sql = "
    UPDATE OS_Empresas
    SET
        NomeFantasia = :NomeFantasia,
        RazaoSocial = :RazaoSocial,
        Cnpj = :Cnpj,
        Email = :Email,
        Telefone = :Telefone,
        WhatsApp = :WhatsApp,
        Slug = :Slug
    WHERE EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":NomeFantasia", $nomeFantasia);
$stmt->bindValue(":RazaoSocial", $razaoSocial !== "" ? $razaoSocial : null);
$stmt->bindValue(":Cnpj", $cnpj !== "" ? $cnpj : null);
$stmt->bindValue(":Email", $email !== "" ? $email : null);
$stmt->bindValue(":Telefone", $telefone !== "" ? $telefone : null);
$stmt->bindValue(":WhatsApp", $whatsApp !== "" ? $whatsApp : null);
$stmt->bindValue(":Slug", $slug);
$stmt->bindValue(":EmpresaId", $empresaIdSessao, PDO::PARAM_INT);

$stmt->execute();

header("Location: editar.php?sucesso=Dados da empresa atualizados com sucesso.");
exit;