<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/planos.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";
require_once "../includes/demo.php";

bloquearAcaoDemo();
csrfValidarTokenPost();

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioId = (int)($_SESSION["UsuarioId"] ?? 0);
$planoSolicitadoId = (int)($_POST["PlanoSolicitadoId"] ?? 0);
$mensagem = trim($_POST["Mensagem"] ?? "");

function redirecionarSolicitacaoPlanoErro($mensagem)
{
    header("Location: meu_plano.php?erro=" . urlencode($mensagem));
    exit;
}

if ($planoSolicitadoId <= 0) {
    redirecionarSolicitacaoPlanoErro("Plano solicitado inválido.");
}

$planoAtual = obterPlanoEmpresa($conn, $empresaId);
$planoAtualId = $planoAtual ? (int)$planoAtual["PlanoId"] : null;

if ($planoAtualId !== null && $planoAtualId === $planoSolicitadoId) {
    redirecionarSolicitacaoPlanoErro("Este já é o plano atual da empresa.");
}

$sqlPlano = "
    SELECT
        PlanoId,
        Nome,
        Slug,
        Ativo
    FROM OS_Planos
    WHERE PlanoId = :PlanoId
      AND Ativo = 1
      AND Slug <> 'teste-assistido'
";

$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bindValue(":PlanoId", $planoSolicitadoId, PDO::PARAM_INT);
$stmtPlano->execute();

$planoSolicitado = $stmtPlano->fetch(PDO::FETCH_ASSOC);

if (!$planoSolicitado) {
    redirecionarSolicitacaoPlanoErro("Plano solicitado não encontrado ou indisponível.");
}

$sqlPendente = "
    SELECT COUNT(*)
    FROM OS_SolicitacoesPlano
    WHERE EmpresaId = :EmpresaId
      AND Status = 'Pendente'
";

$stmtPendente = $conn->prepare($sqlPendente);
$stmtPendente->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtPendente->execute();

if ((int)$stmtPendente->fetchColumn() > 0) {
    redirecionarSolicitacaoPlanoErro("Já existe uma solicitação de alteração de plano pendente para esta empresa.");
}

try {
    $sqlInserir = "
        INSERT INTO OS_SolicitacoesPlano
        (
            EmpresaId,
            PlanoAtualId,
            PlanoSolicitadoId,
            UsuarioId,
            Status,
            Mensagem,
            DataSolicitacao
        )
        VALUES
        (
            :EmpresaId,
            :PlanoAtualId,
            :PlanoSolicitadoId,
            :UsuarioId,
            'Pendente',
            :Mensagem,
            GETDATE()
        )
    ";

    $stmtInserir = $conn->prepare($sqlInserir);
    $stmtInserir->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtInserir->bindValue(":PlanoAtualId", $planoAtualId, $planoAtualId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmtInserir->bindValue(":PlanoSolicitadoId", $planoSolicitadoId, PDO::PARAM_INT);
    $stmtInserir->bindValue(":UsuarioId", $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmtInserir->bindValue(":Mensagem", $mensagem !== "" ? $mensagem : null, $mensagem !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInserir->execute();

    registrarAuditoria(
        $conn,
        "SOLICITACAO_ALTERACAO_PLANO",
        "OS_SolicitacoesPlano",
        null,
        "Empresa solicitou alteração para o plano " . $planoSolicitado["Nome"] . ".",
        $empresaId,
        $usuarioId > 0 ? $usuarioId : null
    );

    header("Location: meu_plano.php?sucesso=" . urlencode("Solicitação enviada com sucesso. Aguarde análise do suporte."));
    exit;
} catch (Exception $e) {
    redirecionarSolicitacaoPlanoErro("Erro ao registrar solicitação de alteração de plano.");
}
