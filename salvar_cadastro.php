<?php
session_start();

require_once "config/conexao.php";

$nomeFantasia = trim($_POST["NomeFantasia"] ?? "");
$razaoSocial = trim($_POST["RazaoSocial"] ?? "");
$cnpj = trim($_POST["Cnpj"] ?? "");
$telefone = trim($_POST["Telefone"] ?? "");
$whatsApp = trim($_POST["WhatsApp"] ?? "");
$emailEmpresa = trim($_POST["EmailEmpresa"] ?? "");

$nomeUsuario = trim($_POST["NomeUsuario"] ?? "");
$emailUsuario = trim($_POST["EmailUsuario"] ?? "");
$senha = $_POST["Senha"] ?? "";
$confirmarSenha = $_POST["ConfirmarSenha"] ?? "";

function redirecionarCadastroErro($mensagem)
{
    header("Location: cadastro.php?erro=" . urlencode($mensagem));
    exit;
}

function gerarSlugCadastro($texto)
{
    $slug = strtolower(trim($texto));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug === "") {
        $slug = "empresa";
    }

    return $slug;
}

if ($nomeFantasia === "") {
    redirecionarCadastroErro("Nome fantasia é obrigatório.");
}

if ($nomeUsuario === "") {
    redirecionarCadastroErro("Nome do administrador é obrigatório.");
}

if ($emailUsuario === "") {
    redirecionarCadastroErro("E-mail de acesso é obrigatório.");
}

if (!filter_var($emailUsuario, FILTER_VALIDATE_EMAIL)) {
    redirecionarCadastroErro("E-mail de acesso inválido.");
}

if ($emailEmpresa !== "" && !filter_var($emailEmpresa, FILTER_VALIDATE_EMAIL)) {
    redirecionarCadastroErro("E-mail da empresa inválido.");
}

if (strlen($senha) < 6) {
    redirecionarCadastroErro("A senha deve ter no mínimo 6 caracteres.");
}

if ($senha !== $confirmarSenha) {
    redirecionarCadastroErro("A confirmação de senha não confere.");
}

$sqlEmail = "
    SELECT COUNT(*)
    FROM OS_Usuarios
    WHERE Email = :Email
";

$stmtEmail = $conn->prepare($sqlEmail);
$stmtEmail->bindValue(":Email", $emailUsuario);
$stmtEmail->execute();

if ((int)$stmtEmail->fetchColumn() > 0) {
    redirecionarCadastroErro("Já existe um usuário cadastrado com este e-mail.");
}

$slugBase = gerarSlugCadastro($nomeFantasia);
$slug = $slugBase;
$contadorSlug = 1;

while (true) {
    $sqlSlug = "
        SELECT COUNT(*)
        FROM OS_Empresas
        WHERE Slug = :Slug
    ";

    $stmtSlug = $conn->prepare($sqlSlug);
    $stmtSlug->bindValue(":Slug", $slug);
    $stmtSlug->execute();

    if ((int)$stmtSlug->fetchColumn() === 0) {
        break;
    }

    $contadorSlug++;
    $slug = $slugBase . "-" . $contadorSlug;
}

$sqlPlano = "
    SELECT TOP 1 PlanoId
    FROM OS_Planos
    WHERE Slug = 'gratuito'
      AND Ativo = 1
";

$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->execute();

$planoGratuitoId = (int)$stmtPlano->fetchColumn();

if ($planoGratuitoId <= 0) {
    redirecionarCadastroErro("Plano gratuito não encontrado. Verifique a configuração dos planos.");
}

$conn->beginTransaction();

try {
    $sqlEmpresa = "
        INSERT INTO OS_Empresas
        (
            NomeFantasia,
            RazaoSocial,
            Cnpj,
            Email,
            Telefone,
            WhatsApp,
            Slug,
            Ativo
        )
        VALUES
        (
            :NomeFantasia,
            :RazaoSocial,
            :Cnpj,
            :Email,
            :Telefone,
            :WhatsApp,
            :Slug,
            1
        )
    ";

    $stmtEmpresa = $conn->prepare($sqlEmpresa);
    $stmtEmpresa->bindValue(":NomeFantasia", $nomeFantasia);
    $stmtEmpresa->bindValue(":RazaoSocial", $razaoSocial !== "" ? $razaoSocial : null);
    $stmtEmpresa->bindValue(":Cnpj", $cnpj !== "" ? $cnpj : null);
    $stmtEmpresa->bindValue(":Email", $emailEmpresa !== "" ? $emailEmpresa : null);
    $stmtEmpresa->bindValue(":Telefone", $telefone !== "" ? $telefone : null);
    $stmtEmpresa->bindValue(":WhatsApp", $whatsApp !== "" ? $whatsApp : null);
    $stmtEmpresa->bindValue(":Slug", $slug);
    $stmtEmpresa->execute();

    $empresaId = (int)$conn->lastInsertId();

    if ($empresaId <= 0) {
        throw new Exception("Erro ao criar empresa.");
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $sqlUsuario = "
        INSERT INTO OS_Usuarios
        (
            EmpresaId,
            Nome,
            Email,
            SenhaHash,
            Perfil,
            Ativo
        )
        VALUES
        (
            :EmpresaId,
            :Nome,
            :Email,
            :SenhaHash,
            'Admin',
            1
        )
    ";

    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtUsuario->bindValue(":Nome", $nomeUsuario);
    $stmtUsuario->bindValue(":Email", $emailUsuario);
    $stmtUsuario->bindValue(":SenhaHash", $senhaHash);
    $stmtUsuario->execute();

    $sqlAssinatura = "
        INSERT INTO OS_Assinaturas
        (
            EmpresaId,
            PlanoId,
            Status,
            DataInicio
        )
        VALUES
        (
            :EmpresaId,
            :PlanoId,
            'Ativa',
            GETDATE()
        )
    ";

    $stmtAssinatura = $conn->prepare($sqlAssinatura);
    $stmtAssinatura->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtAssinatura->bindValue(":PlanoId", $planoGratuitoId, PDO::PARAM_INT);
    $stmtAssinatura->execute();

    $conn->commit();

    header("Location: login.php?erro=" . urlencode("Conta criada com sucesso. Faça login para acessar o DirectOS."));
    exit;

} catch (Exception $e) {
    $conn->rollBack();

    redirecionarCadastroErro("Erro ao criar conta. Tente novamente.");
}