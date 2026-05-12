<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$statusFiltro = $_GET["status"] ?? "";
$prioridadeFiltro = $_GET["prioridade"] ?? "";
$clienteFiltro = $_GET["cliente"] ?? "";
$dataInicioFiltro = $_GET["data_inicio"] ?? "";
$dataFimFiltro = $_GET["data_fim"] ?? "";

$sqlClientes = "
    SELECT ClienteId, Nome
    FROM OS_Clientes
    WHERE Ativo = 1
    ORDER BY Nome
";

$stmtClientes = $conn->prepare($sqlClientes);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT 
        os.OrdemServicoId,
        os.Titulo,
        os.Status,
        os.Prioridade,
        os.ValorPrevisto,
        os.ValorFinal,
        os.DataAbertura,
        os.DataPrevisao,
        os.DataConclusao,
        c.Nome AS ClienteNome,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
    WHERE 1 = 1
";

$parametros = [];

if ($statusFiltro !== "") {
    $sql .= " AND os.Status = :Status ";
    $parametros[":Status"] = $statusFiltro;
}

if ($prioridadeFiltro !== "") {
    $sql .= " AND os.Prioridade = :Prioridade ";
    $parametros[":Prioridade"] = $prioridadeFiltro;
}

if ($clienteFiltro !== "") {
    $sql .= " AND os.ClienteId = :ClienteId ";
    $parametros[":ClienteId"] = $clienteFiltro;
}

if ($dataInicioFiltro !== "") {
    $sql .= " AND CAST(os.DataAbertura AS DATE) >= :DataInicio ";
    $parametros[":DataInicio"] = $dataInicioFiltro;
}

if ($dataFimFiltro !== "") {
    $sql .= " AND CAST(os.DataAbertura AS DATE) <= :DataFim ";
    $parametros[":DataFim"] = $dataFimFiltro;
}

$sql .= " ORDER BY os.OrdemServicoId DESC ";

$stmt = $conn->prepare($sql);

foreach ($parametros as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

$stmt->execute();

$ordens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Ordens de Serviço</h3>
            <p class="text-muted mb-0">Acompanhamento das ordens abertas, em andamento e concluídas</p>
        </div>

        <a href="cadastrar.php" class="btn btn-primary">
            Nova OS
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            Filtros
        </div>

        <div class="card-body">
            <form method="get" action="listar.php">

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            <option value="Aberta" <?= $statusFiltro === "Aberta" ? "selected" : "" ?>>Aberta</option>
                            <option value="Em andamento" <?= $statusFiltro === "Em andamento" ? "selected" : "" ?>>Em andamento</option>
                            <option value="Aguardando cliente" <?= $statusFiltro === "Aguardando cliente" ? "selected" : "" ?>>Aguardando cliente</option>
                            <option value="Aguardando peça" <?= $statusFiltro === "Aguardando peça" ? "selected" : "" ?>>Aguardando peça</option>
                            <option value="Concluída" <?= $statusFiltro === "Concluída" ? "selected" : "" ?>>Concluída</option>
                            <option value="Cancelada" <?= $statusFiltro === "Cancelada" ? "selected" : "" ?>>Cancelada</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Prioridade</label>
                        <select name="prioridade" class="form-control">
                            <option value="">Todas</option>
                            <option value="Baixa" <?= $prioridadeFiltro === "Baixa" ? "selected" : "" ?>>Baixa</option>
                            <option value="Normal" <?= $prioridadeFiltro === "Normal" ? "selected" : "" ?>>Normal</option>
                            <option value="Alta" <?= $prioridadeFiltro === "Alta" ? "selected" : "" ?>>Alta</option>
                            <option value="Urgente" <?= $prioridadeFiltro === "Urgente" ? "selected" : "" ?>>Urgente</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cliente</label>
                        <select name="cliente" class="form-control">
                            <option value="">Todos</option>

                            <?php foreach ($clientes as $cliente): ?>
                                <option 
                                    value="<?= $cliente["ClienteId"] ?>"
                                    <?= (string)$clienteFiltro === (string)$cliente["ClienteId"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($cliente["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Data inicial</label>
                        <input type="date" name="data_inicio" class="form-control"
                               value="<?= htmlspecialchars($dataInicioFiltro) ?>">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Data final</label>
                        <input type="date" name="data_fim" class="form-control"
                               value="<?= htmlspecialchars($dataFimFiltro) ?>">
                    </div>

                    <div class="col-md-6 mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Filtrar
                        </button>

                        <a href="listar.php" class="btn btn-secondary">
                            Limpar
                        </a>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span>Resultado</span>
            <span><?= count($ordens) ?> registro(s)</span>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nº OS</th>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Prioridade</th>
                            <th>Abertura</th>
                            <th>Previsão</th>
                            <th>Valor</th>
                            <th width="260">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($ordens) === 0): ?>
                            <tr>
                                <td colspan="10" class="text-center">
                                    Nenhuma ordem de serviço encontrada.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($ordens as $ordem): ?>
                            <tr>
                                <td><?= $ordem["OrdemServicoId"] ?></td>

                                <td><?= htmlspecialchars($ordem["ClienteNome"] ?? "") ?></td>

                                <td><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></td>

                                <td><?= htmlspecialchars($ordem["Titulo"] ?? "") ?></td>

                                <td>
                                    <?php
                                        $status = $ordem["Status"];
                                        $classeStatus = "bg-secondary";

                                        if ($status === "Aberta") {
                                            $classeStatus = "bg-primary";
                                        } elseif ($status === "Em andamento") {
                                            $classeStatus = "bg-warning text-dark";
                                        } elseif ($status === "Concluída") {
                                            $classeStatus = "bg-success";
                                        } elseif ($status === "Cancelada") {
                                            $classeStatus = "bg-danger";
                                        }
                                    ?>

                                    <span class="badge <?= $classeStatus ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                        $prioridade = $ordem["Prioridade"];
                                        $classePrioridade = "bg-secondary";

                                        if ($prioridade === "Baixa") {
                                            $classePrioridade = "bg-info text-dark";
                                        } elseif ($prioridade === "Alta") {
                                            $classePrioridade = "bg-warning text-dark";
                                        } elseif ($prioridade === "Urgente") {
                                            $classePrioridade = "bg-danger";
                                        }
                                    ?>

                                    <span class="badge <?= $classePrioridade ?>">
                                        <?= htmlspecialchars($prioridade) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= !empty($ordem["DataAbertura"]) 
                                        ? date("d/m/Y", strtotime($ordem["DataAbertura"])) 
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
                                    R$ <?= number_format((float)($ordem["ValorFinal"] ?? $ordem["ValorPrevisto"] ?? 0), 2, ",", ".") ?>
                                </td>

                                <td>
                                    <a href="visualizar.php?id=<?= $ordem["OrdemServicoId"] ?>" 
                                       class="btn btn-sm btn-info">
                                        Ver
                                    </a>

                                    <a href="editar.php?id=<?= $ordem["OrdemServicoId"] ?>" 
                                       class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <a href="excluir.php?id=<?= $ordem["OrdemServicoId"] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Deseja realmente excluir esta OS?')">
                                        Inativar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>