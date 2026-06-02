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
        os.DataAbertura,
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

$sqlRecebimentos = "
    SELECT
        r.RecebimentoId,
        r.ValorRecebido,
        r.FormaPagamento,
        r.DataRecebimento,
        r.Observacao,
        r.DataCadastro,
        u.Nome AS UsuarioNome
    FROM OS_Recebimentos r
    LEFT JOIN OS_Usuarios u 
        ON u.UsuarioId = r.UsuarioId
       AND u.EmpresaId = r.EmpresaId
    WHERE r.OrdemServicoId = :OrdemServicoId
      AND r.EmpresaId = :EmpresaId
    ORDER BY r.DataRecebimento DESC, r.RecebimentoId DESC
";

$stmtRecebimentos = $conn->prepare($sqlRecebimentos);
$stmtRecebimentos->bindValue(":OrdemServicoId", $id, PDO::PARAM_INT);
$stmtRecebimentos->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtRecebimentos->execute();

$recebimentos = $stmtRecebimentos->fetchAll(PDO::FETCH_ASSOC);

$codigoOS = $ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT));

$valorReferencia = (float)($ordem["ValorFinal"] ?? 0);

if ($valorReferencia <= 0) {
    $valorReferencia = (float)($ordem["ValorPrevisto"] ?? 0);
}

$totalRecebido = 0;

foreach ($recebimentos as $recebimento) {
    $totalRecebido += (float)($recebimento["ValorRecebido"] ?? 0);
}

$saldoAtual = $valorReferencia - $totalRecebido;

if ($saldoAtual < 0) {
    $saldoAtual = 0;
}

$mensagem = trim($_GET["mensagem"] ?? "");

function dinheiroRecebimento($valor)
{
    return "R$ " . number_format((float)$valor, 2, ",", ".");
}

function dataRecebimento($data)
{
    if (empty($data)) {
        return "-";
    }

    return date("d/m/Y", strtotime($data));
}

function classeStatusFinanceiroRecebimento($status)
{
    if ($status === "Pago") {
        return "bg-success";
    }

    if ($status === "Parcial") {
        return "bg-warning text-dark";
    }

    if ($status === "Cancelado") {
        return "bg-danger";
    }

    return "bg-secondary";
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                Recebimentos da OS <?= htmlspecialchars($codigoOS) ?>
            </h3>

            <p>
                <?= htmlspecialchars($ordem["Titulo"] ?? "") ?>
            </p>
        </div>

        <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <?php if ($mensagem !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Valor da OS</div>
                    <h4 class="mb-0 mt-2">
                        <?= dinheiroRecebimento($valorReferencia) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100 border border-success">
                <div class="card-body">
                    <div class="small text-muted">Total recebido</div>
                    <h4 class="mb-0 mt-2 text-success">
                        <?= dinheiroRecebimento($totalRecebido) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100 border border-warning">
                <div class="card-body">
                    <div class="small text-muted">Saldo</div>
                    <h4 class="mb-0 mt-2 <?= $saldoAtual > 0 ? "text-warning" : "text-success" ?>">
                        <?= dinheiroRecebimento($saldoAtual) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Status financeiro</div>

                    <span class="badge <?= classeStatusFinanceiroRecebimento($ordem["StatusFinanceiro"] ?? "Pendente") ?> mt-2">
                        <?= htmlspecialchars($ordem["StatusFinanceiro"] ?? "Pendente") ?>
                    </span>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3">

        <div class="col-lg-5">
            <div class="card form-card">
                <div class="card-header">
                    Novo recebimento
                </div>

                <div class="card-body">

                    <form method="post" action="salvar_recebimento.php">
                        <?= csrfInput() ?>

                        <input type="hidden" name="OrdemServicoId" value="<?= (int)$ordem["OrdemServicoId"] ?>">

                        <div class="mb-3">
                            <label class="form-label">Valor recebido *</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                name="ValorRecebido" 
                                class="form-control"
                                required
                                value="<?= $saldoAtual > 0 ? htmlspecialchars(number_format($saldoAtual, 2, ".", "")) : "" ?>"
                            >

                            <div class="input-help mt-2">
                                Informe o valor deste pagamento. O sistema somará todos os recebimentos da OS.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Forma de pagamento</label>

                            <select name="FormaPagamento" class="form-control">
                                <option value="">Não informado</option>
                                <option value="Dinheiro">Dinheiro</option>
                                <option value="Pix">Pix</option>
                                <option value="Cartão de crédito">Cartão de crédito</option>
                                <option value="Cartão de débito">Cartão de débito</option>
                                <option value="Boleto">Boleto</option>
                                <option value="Transferência">Transferência</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data do recebimento *</label>
                            <input 
                                type="date" 
                                name="DataRecebimento" 
                                class="form-control"
                                required
                                value="<?= date("Y-m-d") ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observação</label>
                            <textarea 
                                name="Observacao" 
                                class="form-control" 
                                rows="4"
                                placeholder="Ex.: pagamento parcial, sinal, restante no cartão..."
                            ></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                Registrar Recebimento
                            </button>

                            <a href="visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card form-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Histórico de recebimentos</span>

                    <span class="badge bg-primary">
                        <?= count($recebimentos) ?> lançamento(s)
                    </span>
                </div>

                <div class="card-body p-0">

                    <?php if (count($recebimentos) === 0): ?>
                        <div class="empty-state">
                            Nenhum recebimento registrado para esta OS.
                        </div>
                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-os mb-0">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Valor</th>
                                        <th>Forma</th>
                                        <th>Usuário</th>
                                        <th>Observação</th>
                                        <th width="170">Ação</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($recebimentos as $recebimento): ?>
                                        <tr>
                                            <td>
                                                <?= dataRecebimento($recebimento["DataRecebimento"] ?? null) ?>

                                                <div class="os-subtitle">
                                                    <?= !empty($recebimento["DataCadastro"])
                                                        ? date("d/m/Y H:i", strtotime($recebimento["DataCadastro"]))
                                                        : ""
                                                    ?>
                                                </div>
                                            </td>

                                            <td>
                                                <strong class="text-success">
                                                    <?= dinheiroRecebimento($recebimento["ValorRecebido"] ?? 0) ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($recebimento["FormaPagamento"] ?? "Não informado") ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($recebimento["UsuarioNome"] ?? "-") ?>
                                            </td>

                                            <td>
                                                <?php if (!empty($recebimento["Observacao"])): ?>
                                                    <span style="white-space: pre-line;">
                                                        <?= nl2br(htmlspecialchars($recebimento["Observacao"])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a 
                                                        href="recibo_recebimento.php?id=<?= (int)$recebimento["RecebimentoId"] ?>" 
                                                        class="btn btn-sm btn-outline-success"
                                                        target="_blank"
                                                    >
                                                        Recibo
                                                    </a>

                                                    <a 
                                                        href="excluir_recebimento.php?id=<?= (int)$recebimento["RecebimentoId"] ?>&os=<?= (int)$ordem["OrdemServicoId"] ?>&<?= csrfTokenUrl() ?>" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Deseja excluir este recebimento? O total pago da OS será recalculado.')"
                                                    >
                                                        Excluir
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>