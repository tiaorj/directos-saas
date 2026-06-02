<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/permissoes.php";
require_once "../includes/funcoes.php";

exigirPerfil(["Admin", "Atendente", "Tecnico"]);

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
        SUM(CASE WHEN os.Status = 'Aberta' THEN 1 ELSE 0 END) AS TotalAbertas,
        SUM(CASE WHEN os.Status = 'Em andamento' THEN 1 ELSE 0 END) AS TotalEmAndamento,
        SUM(CASE WHEN os.Status IN ('Aguardando cliente', 'Aguardando peça') THEN 1 ELSE 0 END) AS TotalAguardando,
        SUM(CASE WHEN os.Status = 'Concluída' THEN 1 ELSE 0 END) AS TotalConcluidas,
        SUM(CASE WHEN os.Status = 'Cancelada' THEN 1 ELSE 0 END) AS TotalCanceladas,
        SUM(CASE 
            WHEN os.DataPrevisao < CAST(GETDATE() AS DATE)
             AND os.Status NOT IN ('Concluída', 'Cancelada')
            THEN 1 ELSE 0 
        END) AS TotalAtrasadas,
        ISNULL(SUM(CASE WHEN os.Status <> 'Cancelada' THEN os.ValorPrevisto ELSE 0 END), 0) AS ValorPrevisto,
        ISNULL(SUM(CASE WHEN os.Status = 'Concluída' THEN os.ValorFinal ELSE 0 END), 0) AS ValorFinalizado
    FROM OS_OrdensServico os
    WHERE {$where}
";

$stmtResumo = $conn->prepare($sqlResumo);

foreach ($params as $chave => $param) {
    $stmtResumo->bindValue($chave, $param[0], $param[1]);
}

$stmtResumo->execute();
$resumo = $stmtResumo->fetch(PDO::FETCH_ASSOC);

$sqlOrdens = "
    SELECT TOP 300
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.Prioridade,
        os.DataAbertura,
        os.DataPrevisao,
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

$stmtOrdens = $conn->prepare($sqlOrdens);

foreach ($params as $chave => $param) {
    $stmtOrdens->bindValue($chave, $param[0], $param[1]);
}

$stmtOrdens->execute();
$ordens = $stmtOrdens->fetchAll(PDO::FETCH_ASSOC);

function classeStatusRelatorio($status)
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

function classePrioridadeRelatorio($prioridade)
{
    if ($prioridade === "Baixa") {
        return "bg-info text-dark";
    }

    if ($prioridade === "Normal") {
        return "bg-secondary";
    }

    if ($prioridade === "Alta") {
        return "bg-warning text-dark";
    }

    if ($prioridade === "Urgente") {
        return "bg-danger";
    }

    return "bg-secondary";
}

function valorResumo($resumo, $campo)
{
    return (int)($resumo[$campo] ?? 0);
}

function dinheiroResumo($resumo, $campo)
{
    return number_format((float)($resumo[$campo] ?? 0), 2, ",", ".");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Relatório de Ordens de Serviço</h3>
            <p>Analise as OS por período, status, cliente e serviço.</p>
        </div>

        <a href="../dashboard.php" class="btn btn-outline-secondary">
            Voltar
        </a>
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

                    <a href="ordens.php" class="btn btn-outline-secondary">
                        Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Total de OS</div>
                    <h3 class="mb-0 mt-2"><?= valorResumo($resumo, "TotalOS") ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Abertas</div>
                    <h3 class="mb-0 mt-2 text-primary"><?= valorResumo($resumo, "TotalAbertas") ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Em andamento</div>
                    <h3 class="mb-0 mt-2 text-warning"><?= valorResumo($resumo, "TotalEmAndamento") ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Aguardando</div>
                    <h3 class="mb-0 mt-2 text-secondary"><?= valorResumo($resumo, "TotalAguardando") ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Concluídas</div>
                    <h3 class="mb-0 mt-2 text-success"><?= valorResumo($resumo, "TotalConcluidas") ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Canceladas</div>
                    <h3 class="mb-0 mt-2 text-danger"><?= valorResumo($resumo, "TotalCanceladas") ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100 border border-danger">
                <div class="card-body">
                    <div class="small text-muted">Atrasadas</div>
                    <h3 class="mb-0 mt-2 text-danger"><?= valorResumo($resumo, "TotalAtrasadas") ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Valor previsto</div>
                    <h4 class="mb-0 mt-2">R$ <?= dinheiroResumo($resumo, "ValorPrevisto") ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Valor finalizado</div>
                    <h4 class="mb-0 mt-2">R$ <?= dinheiroResumo($resumo, "ValorFinalizado") ?></h4>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <span>Ordens encontradas</span>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge bg-primary">
                    <?= count($ordens) ?> registro(s)
                </span>

                <a 
                    href="ordens_exportar_csv.php?<?= htmlspecialchars(http_build_query($_GET)) ?>" 
                    class="btn btn-sm btn-outline-success"
                >
                    Exportar CSV
                </a>
            </div>
        </div>

        <div class="card-body p-0">

            <?php if (count($ordens) === 0): ?>
                <div class="empty-state">
                    Nenhuma ordem de serviço encontrada para os filtros selecionados.
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
                                <th>Prioridade</th>
                                <th>Abertura</th>
                                <th>Previsão</th>
                                <th>Valor Previsto</th>
                                <th>Valor Final</th>
                                <th width="90">Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($ordens as $ordem): ?>
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
                                        <span class="badge <?= classeStatusRelatorio($ordem["Status"] ?? "") ?>">
                                            <?= htmlspecialchars($ordem["Status"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge <?= classePrioridadeRelatorio($ordem["Prioridade"] ?? "") ?>">
                                            <?= htmlspecialchars($ordem["Prioridade"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= !empty($ordem["DataAbertura"])
                                            ? date("d/m/Y H:i", strtotime($ordem["DataAbertura"]))
                                            : "-"
                                        ?>
                                    </td>

                                    <td>
                                        <?= !empty($ordem["DataPrevisao"])
                                            ? date("d/m/Y", strtotime($ordem["DataPrevisao"]))
                                            : "-"
                                        ?>
                                    </td>

                                    <td>
                                        R$ <?= number_format((float)($ordem["ValorPrevisto"] ?? 0), 2, ",", ".") ?>
                                    </td>

                                    <td>
                                        R$ <?= number_format((float)($ordem["ValorFinal"] ?? 0), 2, ",", ".") ?>
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

                <?php if (count($ordens) >= 300): ?>
                    <div class="alert alert-warning m-3">
                        O relatório exibiu os primeiros 300 registros. Refine os filtros para visualizar menos resultados.
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>