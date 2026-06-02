<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";

csrfValidarTokenPost();

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioId = (int)($_SESSION["UsuarioId"] ?? 0);

$ordemServicoId = (int)($_POST["OrdemServicoId"] ?? 0);
$statusFinanceiro = trim($_POST["StatusFinanceiro"] ?? "Pendente");
$formaPagamento = trim($_POST["FormaPagamento"] ?? "");
$valorPago = $_POST["ValorPago"] !== "" ? $_POST["ValorPago"] : null;
$dataPagamento = trim($_POST["DataPagamento"] ?? "");
$observacaoFinanceira = trim($_POST["ObservacaoFinanceira"] ?? "");

if ($ordemServicoId <= 0) {
    die("Ordem de serviço inválida.");
}

exigirOrdemDaEmpresa($conn, $ordemServicoId);

$statusFinanceiroPermitidos = ["Pendente", "Parcial", "Pago", "Cancelado"];

if (!in_array($statusFinanceiro, $statusFinanceiroPermitidos, true)) {
    $statusFinanceiro = "Pendente";
}

if ($formaPagamento === "") {
    $formaPagamento = null;
}

if ($dataPagamento === "") {
    $dataPagamento = null;
}

if ($valorPago !== null && !is_numeric($valorPago)) {
    die("Valor pago inválido.");
}

if ($valorPago !== null && (float)$valorPago < 0) {
    die("Valor pago não pode ser negativo.");
}

$sqlAtual = "
    SELECT
        StatusFinanceiro,
        FormaPagamento,
        ValorPago,
        DataPagamento,
        ObservacaoFinanceira
    FROM OS_OrdensServico
    WHERE OrdemServicoId = :OrdemServicoId
      AND EmpresaId = :EmpresaId
";

$stmtAtual = $conn->prepare($sqlAtual);
$stmtAtual->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmtAtual->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtAtual->execute();

$financeiroAnterior = $stmtAtual->fetch(PDO::FETCH_ASSOC);

$sql = "
    UPDATE OS_OrdensServico
    SET
        StatusFinanceiro = :StatusFinanceiro,
        FormaPagamento = :FormaPagamento,
        ValorPago = :ValorPago,
        DataPagamento = :DataPagamento,
        ObservacaoFinanceira = :ObservacaoFinanceira
    WHERE OrdemServicoId = :OrdemServicoId
      AND EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":StatusFinanceiro", $statusFinanceiro);
$stmt->bindValue(":FormaPagamento", $formaPagamento, $formaPagamento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":ValorPago", $valorPago, $valorPago === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":DataPagamento", $dataPagamento, $dataPagamento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
$stmt->bindValue(":ObservacaoFinanceira", $observacaoFinanceira);
$stmt->bindValue(":OrdemServicoId", $ordemServicoId, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$descricaoHistorico = "Controle financeiro atualizado. Status financeiro: {$statusFinanceiro}.";

if ($valorPago !== null) {
    $descricaoHistorico .= " Valor pago: R$ " . number_format((float)$valorPago, 2, ",", ".") . ".";
}

if ($formaPagamento !== null) {
    $descricaoHistorico .= " Forma de pagamento: {$formaPagamento}.";
}

if ($usuarioId > 0) {
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
    $stmtHistorico->bindValue(":StatusAnterior", $financeiroAnterior["StatusFinanceiro"] ?? null);
    $stmtHistorico->bindValue(":StatusNovo", $statusFinanceiro);
    $stmtHistorico->bindValue(":Descricao", $descricaoHistorico);
    $stmtHistorico->execute();
}

header("Location: visualizar.php?id=" . $ordemServicoId . "&mensagem=" . urlencode("Recebimento atualizado com sucesso."));
exit;