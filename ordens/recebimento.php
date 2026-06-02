<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/seguranca.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    die("Ordem de serviço inválida.");
}

exigirOrdemDaEmpresa($conn, $id);

$sql = "
    SELECT
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.ValorPrevisto,
        os.ValorFinal,
        os.StatusFinanceiro,
        os.FormaPagamento,
        os.ValorPago,
        os.DataPagamento,
        os.ObservacaoFinanceira,
        c.Nome AS ClienteNome,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c 
        ON c.ClienteId = os.ClienteId 
       AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s 
        ON s.ServicoId = os.ServicoId 
       AND s.EmpresaId = os.EmpresaId
    WHERE os.OrdemServicoId = :OrdemServicoId
      AND os.EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}

$codigoOS = $ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT));

$valorReferencia = (float)($ordem["ValorFinal"] ?? 0);

if ($valorReferencia <= 0) {
    $valorReferencia = (float)($ordem["ValorPrevisto"] ?? 0);
}

$valorPagoAtual = (float)($ordem["ValorPago"] ?? 0);
$saldoAtual = $valorReferencia - $valorPagoAtual;

if ($saldoAtual < 0) {
    $saldoAtual = 0;
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                Registrar Recebimento
            </h3>

            <p>
                OS <?= htmlspecialchars($codigoOS) ?> · <?= htmlspecialchars($ordem["Titulo"] ?? "") ?>
            </p>
        </div>

        <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="card form-card mb-3">
        <div class="card-header">
            Resumo da OS
        </div>

        <div class="card-body">
            <div class="row">

                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Cliente</div>
                    <strong><?= htmlspecialchars($ordem["ClienteNome"] ?? "-") ?></strong>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Serviço</div>
                    <strong><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></strong>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Valor previsto</div>
                    <strong>R$ <?= number_format((float)($ordem["ValorPrevisto"] ?? 0), 2, ",", ".") ?></strong>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Valor final</div>
                    <strong>R$ <?= number_format((float)($ordem["ValorFinal"] ?? 0), 2, ",", ".") ?></strong>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Valor pago</div>
                    <strong class="text-success">
                        R$ <?= number_format($valorPagoAtual, 2, ",", ".") ?>
                    </strong>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Saldo estimado</div>
                    <strong class="<?= $saldoAtual > 0 ? "text-warning" : "text-success" ?>">
                        R$ <?= number_format($saldoAtual, 2, ",", ".") ?>
                    </strong>
                </div>

            </div>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Dados do Recebimento
        </div>

        <div class="card-body">

            <form method="post" action="salvar_recebimento.php">
                <?= csrfInput() ?>

                <input type="hidden" name="OrdemServicoId" value="<?= (int)$ordem["OrdemServicoId"] ?>">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status Financeiro</label>
                        <?php $statusFinanceiroAtual = $ordem["StatusFinanceiro"] ?? "Pendente"; ?>

                        <select name="StatusFinanceiro" class="form-control" required>
                            <option value="Pendente" <?= $statusFinanceiroAtual === "Pendente" ? "selected" : "" ?>>
                                Pendente
                            </option>

                            <option value="Parcial" <?= $statusFinanceiroAtual === "Parcial" ? "selected" : "" ?>>
                                Parcial
                            </option>

                            <option value="Pago" <?= $statusFinanceiroAtual === "Pago" ? "selected" : "" ?>>
                                Pago
                            </option>

                            <option value="Cancelado" <?= $statusFinanceiroAtual === "Cancelado" ? "selected" : "" ?>>
                                Cancelado
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Forma de Pagamento</label>
                        <?php $formaPagamentoAtual = $ordem["FormaPagamento"] ?? ""; ?>

                        <select name="FormaPagamento" class="form-control">
                            <option value="">Não informado</option>
                            <option value="Dinheiro" <?= $formaPagamentoAtual === "Dinheiro" ? "selected" : "" ?>>Dinheiro</option>
                            <option value="Pix" <?= $formaPagamentoAtual === "Pix" ? "selected" : "" ?>>Pix</option>
                            <option value="Cartão de crédito" <?= $formaPagamentoAtual === "Cartão de crédito" ? "selected" : "" ?>>Cartão de crédito</option>
                            <option value="Cartão de débito" <?= $formaPagamentoAtual === "Cartão de débito" ? "selected" : "" ?>>Cartão de débito</option>
                            <option value="Boleto" <?= $formaPagamentoAtual === "Boleto" ? "selected" : "" ?>>Boleto</option>
                            <option value="Transferência" <?= $formaPagamentoAtual === "Transferência" ? "selected" : "" ?>>Transferência</option>
                            <option value="Outro" <?= $formaPagamentoAtual === "Outro" ? "selected" : "" ?>>Outro</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor Pago</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="ValorPago" 
                            class="form-control"
                            value="<?= htmlspecialchars($ordem["ValorPago"] ?? "") ?>"
                        >

                        <div class="input-help mt-2">
                            Informe o total já recebido nesta OS.
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data de Pagamento</label>
                        <input 
                            type="date" 
                            name="DataPagamento" 
                            class="form-control"
                            value="<?= !empty($ordem["DataPagamento"]) ? date("Y-m-d", strtotime($ordem["DataPagamento"])) : date("Y-m-d") ?>"
                        >
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Observação Financeira</label>
                        <textarea 
                            name="ObservacaoFinanceira" 
                            class="form-control" 
                            rows="4"
                        ><?= htmlspecialchars($ordem["ObservacaoFinanceira"] ?? "") ?></textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Salvar Recebimento
                    </button>

                    <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>