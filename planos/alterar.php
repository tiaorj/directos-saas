<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$planoId = (int)($_GET["plano"] ?? 0);

if ($planoId <= 0) {
    header("Location: meu_plano.php?erro=Plano inválido.");
    exit;
}

$sqlPlano = "
    SELECT PlanoId, Nome
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