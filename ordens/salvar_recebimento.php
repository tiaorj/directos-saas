<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";
require_once "../includes/demo.php";
bloquearAcaoDemo();
csrfValidarTokenPost();

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioId = (int)($_SESSION["UsuarioId"] ?? 0);

$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$valorRecebido = $_POST["ValorRecebido"] !== "" ? $_POST["ValorRecebido"] : null;
$formaPagamento = trim($_POST["FormaPagamento"] ?? "");
$dataRecebimento = trim($_POST["DataRecebimento"] ?? "");
$observacao = trim($_POST["Observacao"] ?? "");

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

exigirOrdemDaEmpresa($conn, $ordemServicoId);

if ($valorRecebido === null || !is_numeric($valorRecebido)) {
    die("Valor recebido inválido.");
}

if ((float)$valorRecebido <= 0) {
    die("Valor recebido deve ser maior que zero.");
}

if ($dataRecebimento === "") {
    die("Data de recebimento é obrigatória.");
}

if ($formaPagamento === "") {
    $formaPagamento = null;
}

try {
    $conn->beginTransaction();

    $sqlInsert = "
        INSERT INTO OS_Recebimentos
        (
            EmpresaId,
            OrdemServicoId,
            UsuarioId,
            ValorRecebido,
            FormaPagamento,
            DataRecebimento,
            Observacao
        )
        VALUES
        (
            :EmpresaId,
            :OrdemServicoId,
            :UsuarioId,
            :ValorRecebido,
            :FormaPagamento,
            :DataRecebimento,
            :Observacao
        )
    ";

    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtInsert->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmtInsert->bindValue(":UsuarioId", $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmtInsert->bindValue(":ValorRecebido", $valorRecebido);
    $stmtInsert->bindValue(":FormaPagamento", $formaPagamento, $formaPagamento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtInsert->bindValue(":DataRecebimento", $dataRecebimento);
    $stmtInsert->bindValue(":Observacao", $observacao);
    $stmtInsert->execute();

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
    $stmtUpdateOS->bindValue(":FormaPagamento", $formaPagamento, $formaPagamento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtUpdateOS->bindValue(":DataPagamento", $resumo["UltimaDataRecebimento"] ?? $dataRecebimento);
    $stmtUpdateOS->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
    $stmtUpdateOS->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmtUpdateOS->execute();

    if ($usuarioId > 0) {
        $descricaoHistorico = "Recebimento registrado. Valor: R$ " . number_format((float)$valorRecebido, 2, ",", ".") . ".";

        if ($formaPagamento !== null) {
            $descricaoHistorico .= " Forma de pagamento: {$formaPagamento}.";
        }

        $descricaoHistorico .= " Status financeiro: {$statusFinanceiro}.";

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

    header("Location: recebimento.php?id=" . $ordemServicoId . "&mensagem=" . urlencode("Recebimento registrado com sucesso."));
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    if (defined("APP_DEBUG") && APP_DEBUG) {
        die("Erro ao registrar recebimento: " . $e->getMessage());
    }

    die("Erro ao registrar recebimento.");
}