<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/funcoes.php";

exigirPerfil(["Admin", "Atendente"]);

$empresaId = (int)$_SESSION["EmpresaId"];

$dataInicial = trim($_GET["data_inicial"] ?? date("Y-m-01"));
$dataFinal = trim($_GET["data_final"] ?? date("Y-m-d"));
$statusFiltro = trim($_GET["status"] ?? "");
$clienteIdFiltro = (int)($_GET["cliente_id"] ?? 0);
$servicoIdFiltro = (int)($_GET["servico_id"] ?? 0);

$statusPermitidos = [
    "",
    "Aberta",
    "Em andamento",
    "Aguardando cliente",
    "Aguardando peça",
    "Concluída",
    "Cancelada"
];

if (!in_array($statusFiltro, $statusPermitidos, true)) {
    $statusFiltro = "";
}

if ($dataInicial === "") {
    $dataInicial = date("Y-m-01");
}

if ($dataFinal === "") {
    $dataFinal = date("Y-m-d");
}

$sqlClientes = "
    SELECT ClienteId, Nome
    FROM OS_Clientes
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
    ORDER BY Nome
";

$stmtClientes = $conn->prepare($sqlClientes);
$stmtClientes->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$sqlServicos = "
    SELECT ServicoId, Nome
    FROM OS_Servicos
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
    ORDER BY Nome
";

$stmtServicos = $conn->prepare($sqlServicos);
$stmtServicos->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtServicos->execute();
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

$where = "
    os.EmpresaId = :EmpresaId
    AND CAST(os.DataAbertura AS DATE) BETWEEN :DataInicial AND :DataFinal
";

$params = [
    ":EmpresaId" => [$empresaId, PDO::PARAM_INT],
    ":DataInicial" => [$dataInicial, PDO::PARAM_STR],
    ":DataFinal" => [$dataFinal, PDO::PARAM_STR],
];

if ($statusFiltro !== "") {
    $where .= " AND os.Status = :Status ";
    $params[":Status"] = [$statusFiltro, PDO::PARAM_STR];
}

if ($clienteIdFiltro > 0) {
    $where .= " AND os.ClienteId = :ClienteId ";
    $params[":ClienteId"] = [$clienteIdFiltro, PDO::PARAM_INT];
}

if ($servicoIdFiltro > 0) {
    $where .= " AND os.ServicoId = :ServicoId ";
    $params[":ServicoId"] = [$servicoIdFiltro, PDO::PARAM_INT];
}

$sqlResumo = "
    SELECT
        COUNT(*) AS TotalOS,
        SUM(CASE WHEN os.Status = 'Concluída' THEN 1 ELSE 0 END) AS TotalConcluidas,
        SUM(CASE WHEN os.Status <> 'Cancelada' THEN 1 ELSE 0 END) AS TotalValidas,
        SUM(CASE WHEN os.Status = 'Cancelada' THEN 1 ELSE 0 END) AS TotalCanceladas,

        ISNULL(SUM(CASE WHEN os.Status <> 'Cancelada' THEN os.ValorPrevisto ELSE 0 END), 0) AS ValorPrevisto,
        ISNULL(SUM(CASE WHEN os.Status = 'Concluída' THEN os.ValorFinal ELSE 0 END), 0) AS ValorFinalizado,

        ISNULL(SUM(
            CASE 
                WHEN os.Status <> 'Cancelada' 
                THEN ISNULL(os.ValorPrevisto, 0) - ISNULL(os.ValorFinal, 0)
                ELSE 0 
            END
        ), 0) AS ValorEmAberto,

        SUM(CASE 
            WHEN os.Status = 'Concluída' 
             AND (os.ValorFinal IS NULL OR os.ValorFinal = 0)
            THEN 1 ELSE 0 
        END) AS ConcluidasSemValorFinal,

        AVG(CASE 
            WHEN os.Status = 'Concluída' AND os.ValorFinal IS NOT NULL
            THEN os.ValorFinal 
            ELSE NULL 
        END) AS TicketMedioConcluido,
        ISNULL(SUM(ISNULL(os.ValorPago, 0)), 0) AS ValorRecebido,

        ISNULL(SUM(
            CASE 
                WHEN os.StatusFinanceiro IN ('Pendente', 'Parcial')
                THEN ISNULL(os.ValorFinal, ISNULL(os.ValorPrevisto, 0)) - ISNULL(os.ValorPago, 0)
                ELSE 0
            END
        ), 0) AS ValorAReceber,

        SUM(CASE WHEN os.StatusFinanceiro = 'Pago' THEN 1 ELSE 0 END) AS TotalPagas,
        SUM(CASE WHEN os.StatusFinanceiro = 'Parcial' THEN 1 ELSE 0 END) AS TotalParciais,
        SUM(CASE WHEN os.StatusFinanceiro = 'Pendente' THEN 1 ELSE 0 END) AS TotalPendentesFinanceiro        
    FROM OS_OrdensServico os
    WHERE {$where}
";

$stmtResumo = $conn->prepare($sqlResumo);

foreach ($params as $chave => $param) {
    $stmtResumo->bindValue($chave, $param[0], $param[1]);
}

$stmtResumo->execute();
$resumo = $stmtResumo->fetch(PDO::FETCH_ASSOC);

$sqlMensal = "
    SELECT
        FORMAT(os.DataAbertura, 'yyyy-MM') AS AnoMes,
        FORMAT(os.DataAbertura, 'MM/yyyy') AS MesLabel,
        COUNT(*) AS TotalOS,
        ISNULL(SUM(CASE WHEN os.Status <> 'Cancelada' THEN os.ValorPrevisto ELSE 0 END), 0) AS ValorPrevisto,
        ISNULL(SUM(CASE WHEN os.Status = 'Concluída' THEN os.ValorFinal ELSE 0 END), 0) AS ValorFinalizado
    FROM OS_OrdensServico os
    WHERE {$where}
    GROUP BY 
        FORMAT(os.DataAbertura, 'yyyy-MM'),
        FORMAT(os.DataAbertura, 'MM/yyyy')
    ORDER BY AnoMes
";

$stmtMensal = $conn->prepare($sqlMensal);

foreach ($params as $chave => $param) {
    $stmtMensal->bindValue($chave, $param[0], $param[1]);
}

$stmtMensal->execute();
$resumoMensal = $stmtMensal->fetchAll(PDO::FETCH_ASSOC);

$maiorValorMensal = 1;

foreach ($resumoMensal as $mes) {
    $maiorValorMes = max((float)$mes["ValorPrevisto"], (float)$mes["ValorFinalizado"]);

    if ($maiorValorMes > $maiorValorMensal) {
        $maiorValorMensal = $maiorValorMes;
    }
}

$sqlServicosFinanceiro = "
    SELECT TOP 10
        ISNULL(s.Nome, 'Não informado') AS ServicoNome,
        COUNT(*) AS TotalOS,
        ISNULL(SUM(CASE WHEN os.Status <> 'Cancelada' THEN os.ValorPrevisto ELSE 0 END), 0) AS ValorPrevisto,
        ISNULL(SUM(CASE WHEN os.Status = 'Concluída' THEN os.ValorFinal ELSE 0 END), 0) AS ValorFinalizado
    FROM OS_OrdensServico os
    LEFT JOIN OS_Servicos s 
        ON s.ServicoId = os.ServicoId 
       AND s.EmpresaId = os.EmpresaId
    WHERE {$where}
    GROUP BY ISNULL(s.Nome, 'Não informado')
    ORDER BY ISNULL(SUM(CASE WHEN os.Status = 'Concluída' THEN os.ValorFinal ELSE 0 END), 0) DESC
";

$stmtServicosFinanceiro = $conn->prepare($sqlServicosFinanceiro);

foreach ($params as $chave => $param) {
    $stmtServicosFinanceiro->bindValue($chave, $param[0], $param[1]);
}

$stmtServicosFinanceiro->execute();
$servicosFinanceiro = $stmtServicosFinanceiro->fetchAll(PDO::FETCH_ASSOC);

$sqlOrdensFinanceiro = "
    SELECT TOP 300
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.DataAbertura,
        os.DataConclusao,
        os.ValorPrevisto,
        os.ValorFinal,
        c.Nome AS ClienteNome,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c 
        ON c.ClienteId = os.ClienteId 
       AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s 
        ON s.ServicoId = os.ServicoId 
       AND s.EmpresaId = os.EmpresaId
    WHERE {$where}
    ORDER BY os.DataAbertura DESC, os.OrdemServicoId DESC
";

$stmtOrdensFinanceiro = $conn->prepare($sqlOrdensFinanceiro);

foreach ($params as $chave => $param) {
    $stmtOrdensFinanceiro->bindValue($chave, $param[0], $param[1]);
}

$stmtOrdensFinanceiro->execute();
$ordensFinanceiro = $stmtOrdensFinanceiro->fetchAll(PDO::FETCH_ASSOC);

function dinheiroFinanceiro($valor)
{
    return number_format((float)$valor, 2, ",", ".");
}

function inteiroFinanceiro($valor)
{
    return (int)($valor ?? 0);
}

function classeStatusFinanceiro($status)
{
    if ($status === "Aberta") {
        return "bg-primary";
    }

    if ($status === "Em andamento") {
        return "bg-warning text-dark";
    }

    if ($status === "Aguardando cliente" || $status === "Aguardando peça") {
        return "bg-secondary";
    }

    if ($status === "Concluída") {
        return "bg-success";
    }

    if ($status === "Cancelada") {
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
            <h3 class="mb-1">Relatório Financeiro</h3>
            <p>Analise valores previstos, finalizados e pendências financeiras das ordens de serviço.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a 
                href="financeiro_exportar_csv.php?<?= htmlspecialchars(http_build_query($_GET)) ?>" 
                class="btn btn-outline-success"
            >
                Exportar CSV
            </a>

            <a href="../dashboard.php" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="card form-card mb-4">
        <div class="card-header">
            Filtros
        </div>

        <div class="card-body">
            <form method="get">
                <div class="row">

                    <div class="col-md-2 mb-3">
                        <label class="form-label">Data inicial</label>
                        <input 
                            type="date" 
                            name="data_inicial" 
                            class="form-control" 
                            value="<?= htmlspecialchars($dataInicial) ?>"
                        >
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label">Data final</label>
                        <input 
                            type="date" 
                            name="data_final" 
                            class="form-control" 
                            value="<?= htmlspecialchars($dataFinal) ?>"
                        >
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>

                            <?php foreach ($statusPermitidos as $statusOpcao): ?>
                                <?php if ($statusOpcao === "") continue; ?>

                                <option 
                                    value="<?= htmlspecialchars($statusOpcao) ?>"
                                    <?= $statusFiltro === $statusOpcao ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($statusOpcao) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Cliente</label>
                        <select name="cliente_id" class="form-control">
                            <option value="0">Todos</option>

                            <?php foreach ($clientes as $cliente): ?>
                                <option 
                                    value="<?= (int)$cliente["ClienteId"] ?>"
                                    <?= $clienteIdFiltro === (int)$cliente["ClienteId"] ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($cliente["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Serviço</label>
                        <select name="servico_id" class="form-control">
                            <option value="0">Todos</option>

                            <?php foreach ($servicos as $servico): ?>
                                <option 
                                    value="<?= (int)$servico["ServicoId"] ?>"
                                    <?= $servicoIdFiltro === (int)$servico["ServicoId"] ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($servico["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Filtrar
                    </button>

                    <a href="financeiro.php" class="btn btn-outline-secondary">
                        Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">

<div class="col-md-3">
    <div class="card shadow-sm h-100 border border-success">
        <div class="card-body">
            <div class="small text-muted">Valor recebido</div>
            <h3 class="mb-0 mt-2 text-success">
                R$ <?= dinheiroFinanceiro($resumo["ValorRecebido"] ?? 0) ?>
            </h3>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card shadow-sm h-100 border border-warning">
        <div class="card-body">
            <div class="small text-muted">Valor a receber</div>
            <h3 class="mb-0 mt-2 text-warning">
                R$ <?= dinheiroFinanceiro($resumo["ValorAReceber"] ?? 0) ?>
            </h3>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card shadow-sm h-100">
        <div class="card-body">
            <div class="small text-muted">Pagas</div>
            <h3 class="mb-0 mt-2 text-success">
                <?= inteiroFinanceiro($resumo["TotalPagas"] ?? 0) ?>
            </h3>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card shadow-sm h-100">
        <div class="card-body">
            <div class="small text-muted">Pendentes financeiro</div>
            <h3 class="mb-0 mt-2 text-warning">
                <?= inteiroFinanceiro($resumo["TotalPendentesFinanceiro"] ?? 0) ?>
            </h3>
        </div>
    </div>
</div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Valor previsto</div>
                    <h3 class="mb-0 mt-2">
                        R$ <?= dinheiroFinanceiro($resumo["ValorPrevisto"] ?? 0) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Valor finalizado</div>
                    <h3 class="mb-0 mt-2 text-success">
                        R$ <?= dinheiroFinanceiro($resumo["ValorFinalizado"] ?? 0) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100 border border-warning">
                <div class="card-body">
                    <div class="small text-muted">Valor em aberto</div>
                    <h3 class="mb-0 mt-2 text-warning">
                        R$ <?= dinheiroFinanceiro($resumo["ValorEmAberto"] ?? 0) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Ticket médio concluído</div>
                    <h3 class="mb-0 mt-2">
                        R$ <?= dinheiroFinanceiro($resumo["TicketMedioConcluido"] ?? 0) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">OS no filtro</div>
                    <h3 class="mb-0 mt-2">
                        <?= inteiroFinanceiro($resumo["TotalOS"] ?? 0) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Concluídas</div>
                    <h3 class="mb-0 mt-2 text-success">
                        <?= inteiroFinanceiro($resumo["TotalConcluidas"] ?? 0) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100 border border-danger">
                <div class="card-body">
                    <div class="small text-muted">Concluídas sem valor final</div>
                    <h3 class="mb-0 mt-2 text-danger">
                        <?= inteiroFinanceiro($resumo["ConcluidasSemValorFinal"] ?? 0) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Canceladas</div>
                    <h3 class="mb-0 mt-2 text-danger">
                        <?= inteiroFinanceiro($resumo["TotalCanceladas"] ?? 0) ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Evolução financeira mensal</span>

            <span class="badge bg-primary">
                Período filtrado
            </span>
        </div>

        <div class="card-body">

            <?php if (count($resumoMensal) === 0): ?>
                <div class="empty-state">
                    Nenhum dado financeiro encontrado para o período.
                </div>
            <?php else: ?>

                <div class="row g-3">
                    <?php foreach ($resumoMensal as $mes): ?>
                        <?php
                            $valorPrevistoMes = (float)($mes["ValorPrevisto"] ?? 0);
                            $valorFinalizadoMes = (float)($mes["ValorFinalizado"] ?? 0);

                            $percentualPrevisto = $maiorValorMensal > 0
                                ? round(($valorPrevistoMes / $maiorValorMensal) * 100)
                                : 0;

                            $percentualFinalizado = $maiorValorMensal > 0
                                ? round(($valorFinalizadoMes / $maiorValorMensal) * 100)
                                : 0;
                        ?>

                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <strong><?= htmlspecialchars($mes["MesLabel"] ?? "-") ?></strong>

                                    <span class="badge bg-secondary">
                                        <?= (int)($mes["TotalOS"] ?? 0) ?> OS
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>Previsto</span>
                                        <strong>R$ <?= dinheiroFinanceiro($valorPrevistoMes) ?></strong>
                                    </div>

                                    <div class="progress" style="height: 10px;">
                                        <div 
                                            class="progress-bar bg-primary" 
                                            style="width: <?= (int)$percentualPrevisto ?>%;">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>Finalizado</span>
                                        <strong>R$ <?= dinheiroFinanceiro($valorFinalizadoMes) ?></strong>
                                    </div>

                                    <div class="progress" style="height: 10px;">
                                        <div 
                                            class="progress-bar bg-success" 
                                            style="width: <?= (int)$percentualFinalizado ?>%;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Serviços por valor finalizado
                </div>

                <div class="card-body p-0">

                    <?php if (count($servicosFinanceiro) === 0): ?>
                        <div class="empty-state">
                            Nenhum serviço encontrado.
                        </div>
                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-os mb-0">
                                <thead>
                                    <tr>
                                        <th>Serviço</th>
                                        <th>OS</th>
                                        <th>Previsto</th>
                                        <th>Finalizado</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($servicosFinanceiro as $servico): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($servico["ServicoNome"] ?? "-") ?></strong>
                                            </td>

                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= (int)($servico["TotalOS"] ?? 0) ?>
                                                </span>
                                            </td>

                                            <td>
                                                R$ <?= dinheiroFinanceiro($servico["ValorPrevisto"] ?? 0) ?>
                                            </td>

                                            <td>
                                                <strong class="text-success">
                                                    R$ <?= dinheiroFinanceiro($servico["ValorFinalizado"] ?? 0) ?>
                                                </strong>
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

        <div class="col-lg-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Alertas financeiros
                </div>

                <div class="card-body">
                    <?php if ((int)($resumo["ConcluidasSemValorFinal"] ?? 0) > 0): ?>
                        <div class="alert alert-warning">
                            <strong>Atenção:</strong>
                            existem <?= (int)$resumo["ConcluidasSemValorFinal"] ?> OS concluída(s) sem valor final.
                            Isso pode deixar o faturamento do período incorreto.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <strong>Tudo certo:</strong>
                            não há OS concluídas sem valor final no filtro atual.
                        </div>
                    <?php endif; ?>

                    <?php if ((float)($resumo["ValorEmAberto"] ?? 0) > 0): ?>
                        <div class="alert alert-light border">
                            <strong>Valor em aberto:</strong>
                            existe uma diferença de R$ <?= dinheiroFinanceiro($resumo["ValorEmAberto"] ?? 0) ?>
                            entre valores previstos e finalizados.
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-0">
                        Nesta primeira versão, o financeiro usa os valores da própria OS.
                        Futuramente podemos evoluir para contas a receber, pagamentos parciais e recibos.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Ordens financeiras</span>

            <span class="badge bg-primary">
                <?= count($ordensFinanceiro) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <?php if (count($ordensFinanceiro) === 0): ?>
                <div class="empty-state">
                    Nenhuma ordem encontrada para os filtros selecionados.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Serviço</th>
                                <th>Status</th>
                                <th>Abertura</th>
                                <th>Conclusão</th>
                                <th>Previsto</th>
                                <th>Final</th>
                                <th>Diferença</th>
                                <th width="90">Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($ordensFinanceiro as $ordem): ?>
                                <?php
                                    $valorPrevisto = (float)($ordem["ValorPrevisto"] ?? 0);
                                    $valorFinal = (float)($ordem["ValorFinal"] ?? 0);
                                    $diferenca = $valorPrevisto - $valorFinal;
                                ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(formatarCodigoOS($ordem["OrdemServicoId"], $ordem["CodigoOS"] ?? null, $ordem["DataAbertura"] ?? null)) ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            <?= htmlspecialchars($ordem["Titulo"] ?? "") ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ordem["ClienteNome"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= classeStatusFinanceiro($ordem["Status"] ?? "") ?>">
                                            <?= htmlspecialchars($ordem["Status"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= !empty($ordem["DataAbertura"])
                                            ? date("d/m/Y", strtotime($ordem["DataAbertura"]))
                                            : "-"
                                        ?>
                                    </td>

                                    <td>
                                        <?= !empty($ordem["DataConclusao"])
                                            ? date("d/m/Y", strtotime($ordem["DataConclusao"]))
                                            : "-"
                                        ?>
                                    </td>

                                    <td>
                                        R$ <?= dinheiroFinanceiro($valorPrevisto) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            R$ <?= dinheiroFinanceiro($valorFinal) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php if ($diferenca > 0): ?>
                                            <span class="text-warning">
                                                R$ <?= dinheiroFinanceiro($diferenca) ?>
                                            </span>
                                        <?php elseif ($diferenca < 0): ?>
                                            <span class="text-success">
                                                + R$ <?= dinheiroFinanceiro(abs($diferenca)) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                R$ 0,00
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <a 
                                            href="../ordens/visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" 
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($ordensFinanceiro) >= 300): ?>
                    <div class="alert alert-warning m-3">
                        O relatório exibiu os primeiros 300 registros. Refine os filtros para visualizar menos resultados.
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>