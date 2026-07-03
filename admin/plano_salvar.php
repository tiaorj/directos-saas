<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";

exigirPerfil(["SuperAdmin"]);
csrfValidarTokenPost();

function redirecionarPlanoErro($planoId, $mensagem)
{
    $url = "plano_editar.php";

    if ($planoId > 0) {
        $url .= "?id=" . (int)$planoId . "&erro=" . urlencode($mensagem);
    } else {
        $url .= "?erro=" . urlencode($mensagem);
    }

    header("Location: " . $url);
    exit;
}

function normalizarSlugPlano($texto)
{
    $slug = strtolower(trim($texto));

    if (function_exists("iconv")) {
        $convertido = @iconv("UTF-8", "ASCII//TRANSLIT", $slug);

        if ($convertido !== false) {
            $slug = $convertido;
        }
    }

    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');

    return $slug;
}

function converterValorDecimalPlano($valor)
{
    $valor = trim((string)$valor);
    $valor = str_replace(["R$", " "], "", $valor);

    if ($valor === "") {
        return null;
    }

    if (str_contains($valor, ",")) {
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", ".", $valor);
    }

    if (!is_numeric($valor)) {
        return null;
    }

    return number_format((float)$valor, 2, ".", "");
}

function converterLimitePlano($valor)
{
    $valor = trim((string)$valor);

    if ($valor === "") {
        return null;
    }

    if (!ctype_digit($valor)) {
        return false;
    }

    return (int)$valor;
}

$planoId = (int)($_POST["PlanoId"] ?? 0);
$nome = trim($_POST["Nome"] ?? "");
$slug = trim($_POST["Slug"] ?? "");
$descricao = trim($_POST["Descricao"] ?? "");
$valorMensal = converterValorDecimalPlano($_POST["ValorMensal"] ?? "");
$limiteOSMes = converterLimitePlano($_POST["LimiteOSMes"] ?? "");
$limiteUsuarios = converterLimitePlano($_POST["LimiteUsuarios"] ?? "");
$permiteAnexos = isset($_POST["PermiteAnexos"]) ? 1 : 0;
$permiteAreaCliente = isset($_POST["PermiteAreaCliente"]) ? 1 : 0;
$permiteWhatsapp = isset($_POST["PermiteWhatsapp"]) ? 1 : 0;
$ativo = isset($_POST["Ativo"]) ? 1 : 0;

if ($nome === "") {
    redirecionarPlanoErro($planoId, "Nome do plano é obrigatório.");
}

if ($slug === "") {
    $slug = normalizarSlugPlano($nome);
} else {
    $slug = normalizarSlugPlano($slug);
}

if ($slug === "") {
    redirecionarPlanoErro($planoId, "Slug inválido.");
}

if ($valorMensal === null || (float)$valorMensal < 0) {
    redirecionarPlanoErro($planoId, "Valor mensal inválido.");
}

if ($limiteOSMes === false) {
    redirecionarPlanoErro($planoId, "Limite de OS/mês inválido.");
}

if ($limiteUsuarios === false) {
    redirecionarPlanoErro($planoId, "Limite de usuários inválido.");
}

$sqlSlug = "
    SELECT COUNT(*)
    FROM OS_Planos
    WHERE Slug = :Slug
      AND PlanoId <> :PlanoId
";

$stmtSlug = $conn->prepare($sqlSlug);
$stmtSlug->bindValue(":Slug", $slug);
$stmtSlug->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
$stmtSlug->execute();

if ((int)$stmtSlug->fetchColumn() > 0) {
    redirecionarPlanoErro($planoId, "Já existe outro plano com este slug.");
}

try {
    if ($planoId > 0) {
        $sql = "
            UPDATE OS_Planos
            SET
                Nome = :Nome,
                Slug = :Slug,
                Descricao = :Descricao,
                LimiteOSMes = :LimiteOSMes,
                LimiteUsuarios = :LimiteUsuarios,
                PermiteAnexos = :PermiteAnexos,
                PermiteAreaCliente = :PermiteAreaCliente,
                PermiteWhatsapp = :PermiteWhatsapp,
                ValorMensal = :ValorMensal,
                Ativo = :Ativo
            WHERE PlanoId = :PlanoId
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
    } else {
        $sql = "
            INSERT INTO OS_Planos
            (
                Nome,
                Slug,
                Descricao,
                LimiteOSMes,
                LimiteUsuarios,
                PermiteAnexos,
                PermiteAreaCliente,
                PermiteWhatsapp,
                ValorMensal,
                Ativo
            )
            OUTPUT INSERTED.PlanoId
            VALUES
            (
                :Nome,
                :Slug,
                :Descricao,
                :LimiteOSMes,
                :LimiteUsuarios,
                :PermiteAnexos,
                :PermiteAreaCliente,
                :PermiteWhatsapp,
                :ValorMensal,
                :Ativo
            )
        ";

        $stmt = $conn->prepare($sql);
    }

    $stmt->bindValue(":Nome", $nome);
    $stmt->bindValue(":Slug", $slug);
    $stmt->bindValue(":Descricao", $descricao !== "" ? $descricao : null, $descricao !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(":LimiteOSMes", $limiteOSMes, $limiteOSMes === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(":LimiteUsuarios", $limiteUsuarios, $limiteUsuarios === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(":PermiteAnexos", $permiteAnexos, PDO::PARAM_INT);
    $stmt->bindValue(":PermiteAreaCliente", $permiteAreaCliente, PDO::PARAM_INT);
    $stmt->bindValue(":PermiteWhatsapp", $permiteWhatsapp, PDO::PARAM_INT);
    $stmt->bindValue(":ValorMensal", $valorMensal);
    $stmt->bindValue(":Ativo", $ativo, PDO::PARAM_INT);

    $stmt->execute();

    if ($planoId <= 0) {
        $planoId = (int)$stmt->fetchColumn();
    }

    registrarAuditoria(
        $conn,
        $planoId > 0 ? "SALVAR_PLANO" : "SALVAR_PLANO",
        "OS_Planos",
        $planoId,
        "Plano " . $nome . " salvo pelo SuperAdmin.",
        null,
        isset($_SESSION["UsuarioId"]) ? (int)$_SESSION["UsuarioId"] : null
    );

    header("Location: planos.php?sucesso=" . urlencode("Plano salvo com sucesso."));
    exit;
} catch (Exception $e) {
    redirecionarPlanoErro($planoId, "Erro ao salvar plano.");
}
