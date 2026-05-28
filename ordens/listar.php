<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
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
$baseUrl = "http://localhost:8080/sistema-os-php-sqlserver";

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
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
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

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Ordens de Serviço</h3>
            <p class="text-muted mb-0">
                Acompanhe, filtre e gerencie as ordens de serviço da empresa.
            </p>
        </div>

        <?php if ($podeCriarOS): ?>
            <a href="cadastrar.php" class="btn btn-primary">
                + Nova OS
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <strong>Filtros</strong>
        </div>

        <div class="section-card-body">
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

                    <div class="col-md-6 mb-3 filter-actions">
                        <button type="submit" class="btn btn-primary">
                            Filtrar
                        </button>

                        <a href="listar.php" class="btn btn-outline-secondary">
                            Limpar
                        </a>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Resultado</strong>

            <span class="badge bg-primary">
                <?= count($ordens) ?> registro(s)
            </span>
        </div>

        <div class="section-card-body p-0">

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
                                <td colspan="10" class="text-center">
                                    Nenhuma ordem de serviço encontrada.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($ordens as $ordem): ?>
                            <tr>
                                <td>
                                    
                                    <span class="os-code">
                                        <?= htmlspecialchars($ordem["CodigoOS"] ?? ("OS-" . date("Y") . "-" . str_pad($ordem["OrdemServicoId"], 6, "0", STR_PAD_LEFT))) ?>
                                        
                                    </span>                                    
                                </td>

                                <td><?= htmlspecialchars($ordem["ClienteNome"] ?? "") ?></td>

                                <td><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></td>
                                
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

                                    <span class="badge badge-status <?= $classeStatus ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= !empty($ordem["DataAbertura"]) 
                                        ? date("d/m/Y", strtotime($ordem["DataAbertura"])) 
                                        : "-" 
                                    ?>
                                    <br>
                                    <?= !empty($ordem["DataPrevisao"]) 
                                        ? date("d/m/Y", strtotime($ordem["DataPrevisao"])) 
                                        : "-" 
                                    ?>
                                </td>

                                <td>
                                    R$ <?= number_format((float)($ordem["ValorFinal"] ?? $ordem["ValorPrevisto"] ?? 0), 2, ",", ".") ?>
                                </td>

                                <td>
                                    <?php
                                        $linkCliente = $baseUrl . "/public/os.php?token=" . $ordem["TokenAcompanhamento"];

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
                                                Atender
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
                                                        Ver detalhes
                                                    </a>
                                                </li>

                                                <?php if ($podeEditarOS): ?>
                                                    <li>
                                                        <a 
                                                            class="dropdown-item" 
                                                            href="editar.php?id=<?= $ordem["OrdemServicoId"] ?>"
                                                        >
                                                            Editar OS
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <li>
                                                    <a 
                                                        class="dropdown-item" 
                                                        href="<?= htmlspecialchars($linkCliente) ?>" 
                                                        target="_blank"
                                                    >
                                                        Abrir link do cliente
                                                    </a>
                                                </li>

                                                <li>
                                                    <button 
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="copiarLinkCliente('<?= htmlspecialchars($linkCliente, ENT_QUOTES) ?>')"
                                                    >
                                                        Copiar link
                                                    </button>
                                                </li>

                                                <?php if ($linkWhatsApp !== ""): ?>
                                                    <li>
                                                        <a 
                                                            class="dropdown-item" 
                                                            href="<?= htmlspecialchars($linkWhatsApp) ?>" 
                                                            target="_blank"
                                                        >
                                                            Enviar por WhatsApp
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($podeCancelarOS): ?>
                                                    <li><hr class="dropdown-divider"></li>

                                                    <li>
                                                        <a 
                                                            class="dropdown-item text-danger" 
                                                            href="excluir.php?id=<?= $ordem["OrdemServicoId"] ?>&<?= csrfTokenUrl() ?>"
                                                            onclick="return confirm('Deseja realmente cancelar esta OS?')"
                                                        >
                                                            Cancelar OS
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
