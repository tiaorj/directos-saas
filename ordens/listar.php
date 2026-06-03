<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../config/config.php";
require_once "../includes/permissoes.php";
require_once "../includes/csrf.php";

$podeCriarOS = usuarioTemPerfil(["Admin", "Atendente"]);
$podeEditarOS = usuarioTemPerfil(["Admin", "Atendente"]);
$podeCancelarOS = usuarioTemPerfil(["Admin"]);
$podeAtenderOS = usuarioTemPerfil(["Admin", "Atendente", "Tecnico"]);

$statusFiltro = $_GET["status"] ?? "";
$prioridadeFiltro = $_GET["prioridade"] ?? "";
$clienteFiltro = $_GET["cliente"] ?? "";
$dataInicioFiltro = $_GET["data_inicio"] ?? "";
$dataFimFiltro = $_GET["data_fim"] ?? "";
$empresaId = $_SESSION["EmpresaId"];

$sqlClientes = "
    SELECT ClienteId, Nome
    FROM OS_Clientes
    WHERE Ativo = 1 
    AND EmpresaId = :EmpresaId
    ORDER BY Nome
";

$stmtClientes = $conn->prepare($sqlClientes);
$stmtClientes->bindValue(":EmpresaId", $empresaId);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT 
        os.OrdemServicoId,
        os.TokenAcompanhamento,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.Prioridade,
        os.ValorPrevisto,
        os.ValorFinal,
        os.DataAbertura,
        os.DataPrevisao,
        os.DataConclusao,
        c.Nome AS ClienteNome,
        c.Telefone AS ClienteTelefone,
        c.Email AS ClienteEmail,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId AND c.EmpresaId = os.EmpresaId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId AND s.EmpresaId = os.EmpresaId
    WHERE os.EmpresaId = :EmpresaId
";

$parametros = [];
$parametros[":EmpresaId"] = $empresaId;

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

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Ordens de Serviço</h3>

            <p>
                Acompanhe atendimentos, prazos, valores e status das ordens de serviço.
            </p>
        </div>

        <?php if ($podeCriarOS): ?>
            <a href="cadastrar.php" class="btn btn-primary">
                + Criar nova OS
            </a>
        <?php endif; ?>
    </div>

    <div class="card form-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Filtros da consulta</span>

            <?php if ($statusFiltro !== "" || $prioridadeFiltro !== "" || $clienteFiltro !== "" || $dataInicioFiltro !== "" || $dataFimFiltro !== ""): ?>
                <span class="badge bg-primary">
                    Filtros ativos
                </span>
            <?php endif; ?>
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
                            Aplicar filtros
                        </button>

                        <a href="listar.php" class="btn btn-outline-secondary">
                            Limpar filtros
                        </a>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <span>Ordens encontradas</span>

            <span class="badge bg-primary">
                <?= count($ordens) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle table-os">
                    <thead class="table-dark">
                        <tr>
                            <th>OS</th>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Status</th>
                            <th>Datas</th>
                            <th>Valor</th>
                            <th class="col-actions">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($ordens) === 0): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    Nenhuma ordem de serviço encontrada para os filtros selecionados.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                        <?php foreach ($ordens as $ordem): ?>
                            <tr>
                                <td>
                                    <strong class="os-code">
                                        <?= htmlspecialchars($ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT))) ?>
                                    </strong>

                                    <div class="os-subtitle">
                                        <?= htmlspecialchars($ordem["Titulo"] ?? "") ?>
                                    </div>
                                </td>

                                <td>
                                    <strong><?= htmlspecialchars($ordem["ClienteNome"] ?? "") ?></strong>

                                    <?php if (!empty($ordem["ClienteTelefone"])): ?>
                                        <div class="os-subtitle">
                                            <?= htmlspecialchars($ordem["ClienteTelefone"]) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?>

                                    <?php if (!empty($ordem["Prioridade"])): ?>
                                        <div class="os-subtitle">
                                            Prioridade: <?= htmlspecialchars($ordem["Prioridade"]) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>                                
                                
                                <td>
                                    <?php
                                        $status = $ordem["Status"];
                                        $classeStatus = "bg-secondary";

                                        if ($status === "Aberta") {
                                            $classeStatus = "bg-primary";
                                        } elseif ($status === "Em andamento") {
                                            $classeStatus = "bg-warning text-dark";
                                        } elseif ($status === "Aguardando cliente" || $status === "Aguardando peça") {
                                            $classeStatus = "bg-secondary";
                                        } elseif ($status === "Concluída") {
                                            $classeStatus = "bg-success";
                                        } elseif ($status === "Cancelada") {
                                            $classeStatus = "bg-danger";
                                        }
                                    ?>

                                    <span class="badge badge-status <?= $classeStatus ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>

                                <td>
                                    <div>
                                        <span class="text-muted small">Abertura:</span>
                                        <strong>
                                            <?= !empty($ordem["DataAbertura"]) 
                                                ? date("d/m/Y", strtotime($ordem["DataAbertura"])) 
                                                : "-" 
                                            ?>
                                        </strong>
                                    </div>

                                    <div class="os-subtitle">
                                        Previsão:
                                        <?= !empty($ordem["DataPrevisao"]) 
                                            ? date("d/m/Y", strtotime($ordem["DataPrevisao"])) 
                                            : "-" 
                                        ?>
                                    </div>

                                    <?php if (!empty($ordem["DataConclusao"])): ?>
                                        <div class="os-subtitle">
                                            Conclusão: <?= date("d/m/Y", strtotime($ordem["DataConclusao"])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($ordem["ValorFinal"]) && (float)$ordem["ValorFinal"] > 0): ?>
                                        <strong>
                                            R$ <?= number_format((float)$ordem["ValorFinal"], 2, ",", ".") ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            Valor final
                                        </div>
                                    <?php elseif (!empty($ordem["ValorPrevisto"]) && (float)$ordem["ValorPrevisto"] > 0): ?>
                                        <strong>
                                            R$ <?= number_format((float)$ordem["ValorPrevisto"], 2, ",", ".") ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            Valor previsto
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php
                                        $linkCliente = APP_URL . "/public/os.php?token=" . urlencode($ordem["TokenAcompanhamento"]);

                                        $telefoneCliente = preg_replace('/\D/', '', $ordem["ClienteTelefone"] ?? "");

                                        $codigoOS = $ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT));

                                        $mensagemWhatsApp = "Olá " . ($ordem["ClienteNome"] ?? "") . 
                                            ", sua ordem de serviço " . $codigoOS . 
                                            " pode ser acompanhada pelo link: " . $linkCliente;

                                        $linkWhatsApp = "";

                                        if ($telefoneCliente !== "") {
                                            $linkWhatsApp = "https://wa.me/55" . $telefoneCliente . "?text=" . urlencode($mensagemWhatsApp);
                                        }
                                    ?>

                                    <div class="d-flex gap-2 action-dropdown">

                                        <?php if ($podeAtenderOS): ?>
                                            <a 
                                                href="atendimento.php?id=<?= $ordem["OrdemServicoId"] ?>" 
                                                class="btn btn-sm btn-success"
                                            >
                                                Atendimento
                                            </a>
                                        <?php endif; ?>

                                        <div class="dropdown">
                                            <button 
                                                class="btn btn-sm btn-outline-dark dropdown-toggle" 
                                                type="button" 
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                Ações
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">

                                                <li>
                                                    <a 
                                                        class="dropdown-item" 
                                                        href="visualizar.php?id=<?= $ordem["OrdemServicoId"] ?>"
                                                    >
                                                        Visualizar OS
                                                    </a>
                                                </li>

                                                <?php if ($podeEditarOS): ?>
                                                    <li>
                                                        <a 
                                                            class="dropdown-item" 
                                                            href="editar.php?id=<?= $ordem["OrdemServicoId"] ?>"
                                                        >
                                                            Editar
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <li>
                                                    <a 
                                                        class="dropdown-item" 
                                                        href="<?= htmlspecialchars($linkCliente) ?>" 
                                                        target="_blank"
                                                    >
                                                        Área do cliente
                                                    </a>
                                                </li>

                                                <li>
                                                    <button 
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="copiarLinkCliente('<?= htmlspecialchars($linkCliente, ENT_QUOTES) ?>')"
                                                    >
                                                        Copiar link do cliente
                                                    </button>
                                                </li>

                                                <?php if ($linkWhatsApp !== ""): ?>
                                                    <li>
                                                        <a 
                                                            class="dropdown-item" 
                                                            href="<?= htmlspecialchars($linkWhatsApp) ?>" 
                                                            target="_blank"
                                                        >
                                                            Enviar link por WhatsApp
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($podeCancelarOS): ?>
                                                    <li><hr class="dropdown-divider"></li>

                                                    <li>
                                                        <a 
                                                            class="dropdown-item text-danger" 
                                                            href="excluir.php?id=<?= $ordem["OrdemServicoId"] ?>&<?= csrfTokenUrl() ?>"
                                                            onclick="return confirm('Deseja realmente cancelar esta ordem de serviço?')"
                                                        >
                                                            Cancelar ordem
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                            </ul>
                                        </div>

                                    </div>                                                                     
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>
<script>
function copiarLinkCliente(link) {
    navigator.clipboard.writeText(link)
        .then(function() {
            alert("Link de acompanhamento copiado!");
        })
        .catch(function() {
            prompt("Copie o link abaixo:", link);
        });
}
</script>
<?php require_once "../includes/footer.php"; ?>
