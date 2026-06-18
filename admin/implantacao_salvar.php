<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";

exigirPerfil(["SuperAdmin"]);
csrfValidarTokenPost();

$nomeFantasia = trim($_POST["NomeFantasia"] ?? "");
$razaoSocial = trim($_POST["RazaoSocial"] ?? "");
$cnpj = trim($_POST["Cnpj"] ?? "");
$emailEmpresa = trim($_POST["EmailEmpresa"] ?? "");
$telefone = trim($_POST["Telefone"] ?? "");
$whatsApp = trim($_POST["WhatsApp"] ?? "");
$segmento = trim($_POST["Segmento"] ?? "");
$nomeUsuario = trim($_POST["NomeUsuario"] ?? "");
$emailUsuario = trim($_POST["EmailUsuario"] ?? "");
$senha = $_POST["Senha"] ?? "";
$planoId = (int)($_POST["PlanoId"] ?? 0);
$dataFimTeste = trim($_POST["DataFimTeste"] ?? "");
$observacaoComercial = trim($_POST["ObservacaoComercial"] ?? "");

function redirecionarImplantacaoErro($mensagem)
{
    header("Location: implantacao_nova.php?erro=" . urlencode($mensagem));
    exit;
}

function gerarSlugImplantacao($texto)
{
    $slug = strtolower(trim($texto));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug === "") {
        $slug = "empresa";
    }

    return $slug;
}

function validarDataImplantacao($data)
{
    if ($data === "") {
        return true;
    }

    $dataObj = DateTime::createFromFormat("Y-m-d", $data);

    return $dataObj && $dataObj->format("Y-m-d") === $data;
}

function registrarErroImplantacaoAssistida($erro)
{
    $mensagem = "[" . date("Y-m-d H:i:s") . "] " . $erro->getMessage() . PHP_EOL;

    @file_put_contents(__DIR__ . "/../logs/implantacao_assistida.log", $mensagem, FILE_APPEND);
}

$segmentosPermitidos = [
    "",
    "oficina",
    "informatica",
    "ar_condicionado",
    "eletronica",
    "servicos_gerais",
    "personalizado"
];

if ($nomeFantasia === "") {
    redirecionarImplantacaoErro("Nome da empresa é obrigatório.");
}

if ($nomeUsuario === "") {
    redirecionarImplantacaoErro("Nome do responsável é obrigatório.");
}

if ($emailUsuario === "") {
    redirecionarImplantacaoErro("E-mail de acesso é obrigatório.");
}

if (!filter_var($emailUsuario, FILTER_VALIDATE_EMAIL)) {
    redirecionarImplantacaoErro("E-mail de acesso inválido.");
}

if ($emailEmpresa !== "" && !filter_var($emailEmpresa, FILTER_VALIDATE_EMAIL)) {
    redirecionarImplantacaoErro("E-mail da empresa inválido.");
}

if (strlen($senha) < 6) {
    redirecionarImplantacaoErro("A senha provisória deve ter no mínimo 6 caracteres.");
}

if ($planoId <= 0) {
    redirecionarImplantacaoErro("Plano inválido.");
}

if (!in_array($segmento, $segmentosPermitidos, true)) {
    redirecionarImplantacaoErro("Segmento inválido.");
}

if (!validarDataImplantacao($dataFimTeste)) {
    redirecionarImplantacaoErro("Data fim do teste inválida.");
}

$dataFimTesteObj = $dataFimTeste !== ""
    ? DateTime::createFromFormat("Y-m-d", $dataFimTeste)
    : new DateTime("+7 days");

$dataFimTesteSql = $dataFimTesteObj->format("Ymd");

$sqlEmail = "
    SELECT COUNT(*)
    FROM OS_Usuarios
    WHERE Email = :Email
";

$stmtEmail = $conn->prepare($sqlEmail);
$stmtEmail->bindValue(":Email", $emailUsuario);
$stmtEmail->execute();

if ((int)$stmtEmail->fetchColumn() > 0) {
    redirecionarImplantacaoErro("Já existe um usuário cadastrado com este e-mail.");
}

$sqlPlano = "
    SELECT TOP 1
        PlanoId,
        Nome,
        Slug
    FROM OS_Planos
    WHERE PlanoId = :PlanoId
      AND Ativo = 1
";

$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
$stmtPlano->execute();

$plano = $stmtPlano->fetch(PDO::FETCH_ASSOC);

if (!$plano) {
    redirecionarImplantacaoErro("Plano não encontrado ou inativo.");
}

$slugBase = gerarSlugImplantacao($nomeFantasia);
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

$statusComercial = ($plano["Slug"] ?? "") === "teste-assistido" ? "Teste" : "Ativa";
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

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
            Ativo,
            DataCadastro,
            OcultarOnboarding,
            Segmento,
            PlanoId,
            StatusComercial,
            DataInicioTeste,
            DataFimTeste,
            ObservacaoComercial
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
            1,
            GETDATE(),
            0,
            :Segmento,
            :PlanoId,
            :StatusComercial,
            GETDATE(),
            CONVERT(DATETIME, :DataFimTeste, 112),
            :ObservacaoComercial
        )
    ";

    $stmtEmpresa = $conn->prepare($sqlEmpresa);
    $stmtEmpresa->bindValue(":NomeFantasia", $nomeFantasia);
    $stmtEmpresa->bindValue(":RazaoSocial", $razaoSocial !== "" ? $razaoSocial : null, $razaoSocial !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtEmpresa->bindValue(":Cnpj", $cnpj !== "" ? $cnpj : null, $cnpj !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtEmpresa->bindValue(":Email", $emailEmpresa !== "" ? $emailEmpresa : null, $emailEmpresa !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtEmpresa->bindValue(":Telefone", $telefone !== "" ? $telefone : null, $telefone !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtEmpresa->bindValue(":WhatsApp", $whatsApp !== "" ? $whatsApp : null, $whatsApp !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtEmpresa->bindValue(":Slug", $slug);
    $stmtEmpresa->bindValue(":Segmento", $segmento !== "" ? $segmento : null, $segmento !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtEmpresa->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
    $stmtEmpresa->bindValue(":StatusComercial", $statusComercial);
    $stmtEmpresa->bindValue(":DataFimTeste", $dataFimTesteSql);
    $stmtEmpresa->bindValue(":ObservacaoComercial", $observacaoComercial !== "" ? $observacaoComercial : null, $observacaoComercial !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtEmpresa->execute();

    $empresaId = (int)$conn->lastInsertId();

    if ($empresaId <= 0) {
        throw new Exception("Erro ao criar empresa.");
    }

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
    $stmtAssinatura->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
    $stmtAssinatura->execute();

    $conn->commit();

    try {
        registrarAuditoria(
            $conn,
            "CRIACAO_IMPLANTACAO_ASSISTIDA",
            "OS_Empresas",
            $empresaId,
            "Empresa criada em implantação assistida no plano " . $plano["Nome"] . ".",
            $empresaId,
            null
        );
    } catch (Exception $erroAuditoria) {
        registrarErroImplantacaoAssistida($erroAuditoria);
    }

    header("Location: empresa.php?id=" . $empresaId . "&sucesso=" . urlencode("Empresa criada em implantação assistida."));
    exit;
} catch (Exception $e) {
    $conn->rollBack();

    registrarErroImplantacaoAssistida($e);

    $mensagemErro = "Erro ao criar implantação assistida.";

    if (defined("APP_DEBUG") && APP_DEBUG) {
        $mensagemErro .= " Detalhe: " . substr($e->getMessage(), 0, 300);
    }

    redirecionarImplantacaoErro($mensagemErro);
}
