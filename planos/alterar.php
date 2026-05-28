<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";
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
";

$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bindValue(":PlanoId", $planoId, PDO::PARAM_INT);
$stmtPlano->execute();

$plano = $stmtPlano->fetch(PDO::FETCH_ASSOC);

if (!$plano) {
    header("Location: meu_plano.php?erro=Plano não encontrado.");
    exit;
}

$sqlPlanoAtual = "
    SELECT TOP 1
        a.AssinaturaId,
        a.PlanoId,
        p.Nome
    FROM OS_Assinaturas a
    INNER JOIN OS_Planos p ON p.PlanoId = a.PlanoId
    WHERE a.EmpresaId = :EmpresaId
      AND a.Status = 'Ativa'
    ORDER BY a.AssinaturaId DESC
";

$stmtPlanoAtual = $conn->prepare($sqlPlanoAtual);
$stmtPlanoAtual->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtPlanoAtual->execute();

$planoAtual = $stmtPlanoAtual->fetch(PDO::FETCH_ASSOC);

if ($planoAtual && (int)$planoAtual["PlanoId"] === (int)$planoId) {
    header("Location: meu_plano.php?erro=Este já é o plano atual da empresa.");
    exit;
}

$totalUsuarios = 0;

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

$totalOSMes = 0;

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
