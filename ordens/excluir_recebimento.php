<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
csrfValidarTokenGet();

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioId = (int)($_SESSION["UsuarioId"] ?? 0);

$recebimentoId = (int)($_GET["id"] ?? 0);
$ordemServicoId = (int)($_GET["os"] ?? 0);

if ($recebimentoId <= 0 || $ordemServicoId <= 0) {
    die("Recebimento inválido.");
}

exigirOrdemDaEmpresa($conn, $ordemServicoId);

try {
    $conn->beginTransaction();

    $sqlRecebimento = "
        SELECT
            RecebimentoId,
            ValorRecebido,
            FormaPagamento
        FROM OS_Recebimentos
        WHERE RecebimentoId = :RecebimentoId
          AND OrdemServicoId = :OrdemServicoId
          AND EmpresaId = :EmpresaId
    ";

    $stmtRecebimento = $conn->prepare($sqlRecebimento);
    $stmtRecebimento->bindValue(":RecebimentoId", $recebimentoId, PDO::PARAM_INT);
    $stmtRecebimento->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmtRecebimento->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtRecebimento->execute();

    $recebimento = $stmtRecebimento->fetch(PDO::FETCH_ASSOC);

    if (!$recebimento) {
        throw new Exception("Recebimento não encontrado.");
    }

    $sqlDelete = "
        DELETE FROM OS_Recebimentos
        WHERE RecebimentoId = :RecebimentoId
          AND OrdemServicoId = :OrdemServicoId
          AND EmpresaId = :EmpresaId
    ";

    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bindValue(":RecebimentoId", $recebimentoId, PDO::PARAM_INT);
    $stmtDelete->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmtDelete->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtDelete->execute();

    $sqlResumo = "
        SELECT
            os.ValorPrevisto,
            os.ValorFinal,
            ISNULL(SUM(r.ValorRecebido), 0) AS TotalRecebido,
            MAX(r.DataRecebimento) AS UltimaDataRecebimento
        FROM OS_OrdensServico os
        LEFT JOIN OS_Recebimentos r
            ON r.OrdemServicoId = os.OrdemServicoId
           AND r.EmpresaId = os.EmpresaId
        WHERE os.OrdemServicoId = :OrdemServicoId
          AND os.EmpresaId = :EmpresaId
        GROUP BY os.ValorPrevisto, os.ValorFinal
    ";

    $stmtResumo = $conn->prepare($sqlResumo);
    $stmtResumo->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmtResumo->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtResumo->execute();

    $resumo = $stmtResumo->fetch(PDO::FETCH_ASSOC);

    $valorReferencia = (float)($resumo["ValorFinal"] ?? 0);

    if ($valorReferencia <= 0) {
        $valorReferencia = (float)($resumo["ValorPrevisto"] ?? 0);
    }

    $totalRecebido = (float)($resumo["TotalRecebido"] ?? 0);

    if ($totalRecebido <= 0) {
        $statusFinanceiro = "Pendente";
    } elseif ($valorReferencia > 0 && $totalRecebido >= $valorReferencia) {
        $statusFinanceiro = "Pago";
    } else {
        $statusFinanceiro = "Parcial";
    }

    $sqlUltimaForma = "
        SELECT TOP 1 FormaPagamento
        FROM OS_Recebimentos
        WHERE OrdemServicoId = :OrdemServicoId
          AND EmpresaId = :EmpresaId
        ORDER BY DataRecebimento DESC, RecebimentoId DESC
    ";

    $stmtUltimaForma = $conn->prepare($sqlUltimaForma);
    $stmtUltimaForma->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmtUltimaForma->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtUltimaForma->execute();

    $ultimaFormaPagamento = $stmtUltimaForma->fetchColumn();

    if ($ultimaFormaPagamento === false) {
        $ultimaFormaPagamento = null;
    }

    $sqlUpdateOS = "
        UPDATE OS_OrdensServico
        SET
            ValorPago = :ValorPago,
            StatusFinanceiro = :StatusFinanceiro,
            FormaPagamento = :FormaPagamento,
            DataPagamento = :DataPagamento
        WHERE OrdemServicoId = :OrdemServicoId
          AND EmpresaId = :EmpresaId
    ";

    $stmtUpdateOS = $conn->prepare($sqlUpdateOS);
    $stmtUpdateOS->bindValue(":ValorPago", $totalRecebido);
    $stmtUpdateOS->bindValue(":StatusFinanceiro", $statusFinanceiro);
    $stmtUpdateOS->bindValue(":FormaPagamento", $ultimaFormaPagamento, $ultimaFormaPagamento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtUpdateOS->bindValue(":DataPagamento", $resumo["UltimaDataRecebimento"] ?? null, empty($resumo["UltimaDataRecebimento"]) ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtUpdateOS->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmtUpdateOS->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtUpdateOS->execute();

    if ($usuarioId > 0) {
        $descricaoHistorico = "Recebimento excluído. Valor removido: R$ " . number_format((float)$recebimento["ValorRecebido"], 2, ",", ".") . ". Status financeiro recalculado: {$statusFinanceiro}.";

        $sqlHistorico = "
            INSERT INTO OS_Historico
            (
                OrdemServicoId,
                UsuarioId,
                StatusAnterior,
                StatusNovo,
                Descricao,
                DataRegistro
            )
            VALUES
            (
                :OrdemServicoId,
                :UsuarioId,
                :StatusAnterior,
                :StatusNovo,
                :Descricao,
                GETDATE()
            )
        ";

        $stmtHistorico = $conn->prepare($sqlHistorico);
        $stmtHistorico->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
        $stmtHistorico->bindValue(":UsuarioId", $usuarioId, PDO::PARAM_INT);
        $stmtHistorico->bindValue(":StatusAnterior", null);
        $stmtHistorico->bindValue(":StatusNovo", $statusFinanceiro);
        $stmtHistorico->bindValue(":Descricao", $descricaoHistorico);
        $stmtHistorico->execute();
    }

    $conn->commit();

    header("Location: recebimento.php?id=" . $ordemServicoId . "&mensagem=" . urlencode("Recebimento excluído e total recalculado."));
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    if (defined("APP_DEBUG") && APP_DEBUG) {
        die("Erro ao excluir recebimento: " . $e->getMessage());
    }

    die("Erro ao excluir recebimento.");
}