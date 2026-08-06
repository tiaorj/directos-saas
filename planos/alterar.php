<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

exigirPerfil(["SuperAdmin"]);

csrfValidarTokenPost();

$empresaId = (int)$_SESSION["EmpresaId"];
$planoId = (int)($_POST["plano"] ?? 0);

if ($planoId <= 0) {
    header("Location: meu_plano.php?erro=Plano inválido.");
    exit;
}

$sqlPlano = "
    SELECT
        PlanoId,
        Nome,
        Slug,
        LimiteOSMes,
        LimiteUsuarios
    FROM OS_Planos
    WHERE PlanoId = :PlanoId
      AND Ativo = 1
      AND Slug IN ('starter', 'profissional', 'empresa', 'teste-assistido')
";

$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
$stmtPlano->execute();

$plano = $stmtPlano->fetch(PDO::FETCH_ASSOC);

if (!$plano) {
    header("Location: meu_plano.php?erro=Plano não encontrado.");
    exit;
}

$sqlEmpresa = "
    SELECT
        EmpresaId,
        PlanoId,
        StatusComercial
    FROM OS_Empresas
    WHERE EmpresaId = :EmpresaId
";

$stmtEmpresa = $conn->prepare($sqlEmpresa);
$stmtEmpresa->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtEmpresa->execute();

$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    header("Location: meu_plano.php?erro=Empresa não encontrada.");
    exit;
}

if (!empty($empresa["PlanoId"]) && (int)$empresa["PlanoId"] === (int)$planoId) {
    header("Location: meu_plano.php?erro=Este já é o plano atual da empresa.");
    exit;
}

$sqlUsuarios = "
    SELECT COUNT(*)
    FROM OS_Usuarios
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
";

$stmtUsuarios = $conn->prepare($sqlUsuarios);
$stmtUsuarios->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtUsuarios->execute();

$totalUsuarios = (int)$stmtUsuarios->fetchColumn();

if ($plano["LimiteUsuarios"] !== null && $plano["LimiteUsuarios"] !== "" && $totalUsuarios > (int)$plano["LimiteUsuarios"]) {
    header("Location: meu_plano.php?erro=Não é possível alterar para este plano. A empresa possui mais usuários ativos do que o limite permitido.");
    exit;
}

$sqlOSMes = "
    SELECT COUNT(*)
    FROM OS_OrdensServico
    WHERE EmpresaId = :EmpresaId
      AND DataAbertura >= DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)
      AND DataAbertura < DATEADD(MONTH, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
";

$stmtOSMes = $conn->prepare($sqlOSMes);
$stmtOSMes->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtOSMes->execute();

$totalOSMes = (int)$stmtOSMes->fetchColumn();

if ($plano["LimiteOSMes"] !== null && $plano["LimiteOSMes"] !== "" && $totalOSMes > (int)$plano["LimiteOSMes"]) {
    header("Location: meu_plano.php?erro=Não é possível alterar para este plano. A empresa já possui mais OS no mês do que o limite permitido.");
    exit;
}

$conn->beginTransaction();

try {
    /*
        Atualiza o plano atual diretamente na empresa.
        Essa passa a ser a fonte principal do plano vigente.
    */

    if (($plano["Slug"] ?? "") === "teste-assistido") {
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

    /*
        Mantém histórico em OS_Assinaturas para compatibilidade e auditoria.
    */

    $sqlCancelar = "
        UPDATE OS_Assinaturas
        SET Status = 'Cancelada',
            DataFim = GETDATE()
        WHERE EmpresaId = :EmpresaId
          AND Status = 'Ativa'
    ";

    $stmtCancelar = $conn->prepare($sqlCancelar);
    $stmtCancelar->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtCancelar->execute();

    $sqlInserir = "
        INSERT INTO OS_Assinaturas (
            EmpresaId,
            PlanoId,
            Status,
            DataInicio
        )
        VALUES (
            :EmpresaId,
            :PlanoId,
            'Ativa',
            GETDATE()
        )
    ";

    $stmtInserir = $conn->prepare($sqlInserir);
    $stmtInserir->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtInserir->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
    $stmtInserir->execute();

    $conn->commit();

    header("Location: meu_plano.php?sucesso=Plano alterado para " . urlencode($plano["Nome"]) . ".");
    exit;

} catch (Exception $e) {
    $conn->rollBack();

    header("Location: meu_plano.php?erro=Erro ao alterar plano.");
    exit;
}