<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
require_once "../includes/auditoria.php";

exigirPerfil(["SuperAdmin"]);
csrfValidarTokenPost();

$solicitacaoId = (int)($_POST["SolicitacaoId"] ?? 0);
$acao = trim($_POST["Acao"] ?? "");
$observacaoAdmin = trim($_POST["ObservacaoAdmin"] ?? "");

function redirecionarProcessamentoPlano($tipo, $mensagem)
{
    header("Location: solicitacoes_planos.php?" . $tipo . "=" . urlencode($mensagem));
    exit;
}

if ($solicitacaoId <= 0) {
    redirecionarProcessamentoPlano("erro", "Solicitação inválida.");
}

if (!in_array($acao, ["aprovar", "recusar"], true)) {
    redirecionarProcessamentoPlano("erro", "Ação inválida.");
}

$sqlSolicitacao = "
    SELECT
        s.SolicitacaoId,
        s.EmpresaId,
        s.PlanoAtualId,
        s.PlanoSolicitadoId,
        s.UsuarioId,
        s.Status,
        e.NomeFantasia AS EmpresaNome,
        e.Ativo AS EmpresaAtiva,
        ps.Nome AS PlanoSolicitadoNome,
        ps.Slug AS PlanoSolicitadoSlug,
        ps.Ativo AS PlanoSolicitadoAtivo
    FROM OS_SolicitacoesPlano s
    INNER JOIN OS_Empresas e ON e.EmpresaId = s.EmpresaId
    INNER JOIN OS_Planos ps ON ps.PlanoId = s.PlanoSolicitadoId
    WHERE s.SolicitacaoId = :SolicitacaoId
";

$stmtSolicitacao = $conn->prepare($sqlSolicitacao);
$stmtSolicitacao->bindValue(":SolicitacaoId", $solicitacaoId, PDO::PARAM_INT);
$stmtSolicitacao->execute();

$solicitacao = $stmtSolicitacao->fetch(PDO::FETCH_ASSOC);

if (!$solicitacao) {
    redirecionarProcessamentoPlano("erro", "Solicitação não encontrada.");
}

if (($solicitacao["Status"] ?? "") !== "Pendente") {
    redirecionarProcessamentoPlano("erro", "Esta solicitação já foi processada.");
}

if ($acao === "recusar") {
    $sqlRecusar = "
        UPDATE OS_SolicitacoesPlano
        SET
            Status = 'Recusada',
            ObservacaoAdmin = :ObservacaoAdmin,
            DataResposta = GETDATE()
        WHERE SolicitacaoId = :SolicitacaoId
    ";

    $stmtRecusar = $conn->prepare($sqlRecusar);
    $stmtRecusar->bindValue(":ObservacaoAdmin", $observacaoAdmin !== "" ? $observacaoAdmin : null, $observacaoAdmin !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtRecusar->bindValue(":SolicitacaoId", $solicitacaoId, PDO::PARAM_INT);
    $stmtRecusar->execute();

    registrarAuditoria(
        $conn,
        "RECUSA_SOLICITACAO_PLANO",
        "OS_SolicitacoesPlano",
        $solicitacaoId,
        "Solicitação de plano recusada.",
        (int)$solicitacao["EmpresaId"],
        isset($_SESSION["UsuarioId"]) ? (int)$_SESSION["UsuarioId"] : null
    );

    redirecionarProcessamentoPlano("sucesso", "Solicitação recusada.");
}

if ((int)($solicitacao["EmpresaAtiva"] ?? 0) !== 1) {
    redirecionarProcessamentoPlano("erro", "Não é possível aprovar plano para empresa inativa.");
}

if ((int)($solicitacao["PlanoSolicitadoAtivo"] ?? 0) !== 1) {
    redirecionarProcessamentoPlano("erro", "O plano solicitado está inativo.");
}

$empresaId = (int)$solicitacao["EmpresaId"];
$planoId = (int)$solicitacao["PlanoSolicitadoId"];

$conn->beginTransaction();

try {
    if (($solicitacao["PlanoSolicitadoSlug"] ?? "") === "teste-assistido") {
        $sqlAtualizarEmpresa = "
            UPDATE OS_Empresas
            SET
                PlanoId = :PlanoId,
                StatusComercial = 'Teste',
                DataInicioTeste = ISNULL(DataInicioTeste, GETDATE()),
                DataFimTeste = ISNULL(DataFimTeste, DATEADD(DAY, 7, GETDATE())),
                ObservacaoComercial = ISNULL(ObservacaoComercial, 'Acesso liberado para teste assistido.')
            WHERE EmpresaId = :EmpresaId
        ";
    } else {
        $sqlAtualizarEmpresa = "
            UPDATE OS_Empresas
            SET
                PlanoId = :PlanoId,
                StatusComercial = 'Ativa',
                DataInicioTeste = NULL,
                DataFimTeste = NULL
            WHERE EmpresaId = :EmpresaId
        ";
    }

    $stmtAtualizarEmpresa = $conn->prepare($sqlAtualizarEmpresa);
    $stmtAtualizarEmpresa->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
    $stmtAtualizarEmpresa->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtAtualizarEmpresa->execute();

    $sqlCancelar = "
        UPDATE OS_Assinaturas
        SET
            Status = 'Cancelada',
            DataFim = GETDATE()
        WHERE EmpresaId = :EmpresaId
          AND Status = 'Ativa'
    ";

    $stmtCancelar = $conn->prepare($sqlCancelar);
    $stmtCancelar->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtCancelar->execute();

    $sqlInserirAssinatura = "
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

    $stmtInserirAssinatura = $conn->prepare($sqlInserirAssinatura);
    $stmtInserirAssinatura->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtInserirAssinatura->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
    $stmtInserirAssinatura->execute();

    $sqlAtualizarSolicitacao = "
        UPDATE OS_SolicitacoesPlano
        SET
            Status = 'Aprovada',
            ObservacaoAdmin = :ObservacaoAdmin,
            DataResposta = GETDATE()
        WHERE SolicitacaoId = :SolicitacaoId
    ";

    $stmtAtualizarSolicitacao = $conn->prepare($sqlAtualizarSolicitacao);
    $stmtAtualizarSolicitacao->bindValue(":ObservacaoAdmin", $observacaoAdmin !== "" ? $observacaoAdmin : null, $observacaoAdmin !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtAtualizarSolicitacao->bindValue(":SolicitacaoId", $solicitacaoId, PDO::PARAM_INT);
    $stmtAtualizarSolicitacao->execute();

    registrarAuditoria(
        $conn,
        "APROVACAO_SOLICITACAO_PLANO",
        "OS_SolicitacoesPlano",
        $solicitacaoId,
        "Solicitação aprovada. Plano alterado para " . $solicitacao["PlanoSolicitadoNome"] . ".",
        $empresaId,
        isset($_SESSION["UsuarioId"]) ? (int)$_SESSION["UsuarioId"] : null
    );

    $conn->commit();

    redirecionarProcessamentoPlano("sucesso", "Solicitação aprovada e plano alterado com sucesso.");
} catch (Exception $e) {
    $conn->rollBack();

    redirecionarProcessamentoPlano("erro", "Erro ao aprovar solicitação de plano.");
}
