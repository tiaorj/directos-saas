<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["SuperAdmin"]);

$filtroEmpresa = trim($_GET["empresa"] ?? "");
$filtroUsuario = trim($_GET["usuario"] ?? "");
$filtroAcao = trim($_GET["acao"] ?? "");
$filtroEntidade = trim($_GET["entidade"] ?? "");
$filtroDataInicio = trim($_GET["data_inicio"] ?? "");
$filtroDataFim = trim($_GET["data_fim"] ?? "");

$where = [];
$params = [];

if ($filtroEmpresa !== "") {
    $where[] = "e.NomeFantasia LIKE :Empresa";
    $params[":Empresa"] = "%" . $filtroEmpresa . "%";
}

if ($filtroUsuario !== "") {
    $where[] = "u.Nome LIKE :Usuario";
    $params[":Usuario"] = "%" . $filtroUsuario . "%";
}

if ($filtroAcao !== "") {
    $where[] = "a.Acao = :Acao";
    $params[":Acao"] = $filtroAcao;
}

if ($filtroEntidade !== "") {
    $where[] = "a.Entidade = :Entidade";
    $params[":Entidade"] = $filtroEntidade;
}

if ($filtroDataInicio !== "") {
    $where[] = "a.DataRegistro >= :DataInicio";
    $params[":DataInicio"] = $filtroDataInicio . " 00:00:00";
}

if ($filtroDataFim !== "") {
    $where[] = "a.DataRegistro <= :DataFim";
    $params[":DataFim"] = $filtroDataFim . " 23:59:59";
}

$sqlWhere = "";

if (count($where) > 0) {
    $sqlWhere = "WHERE " . implode(" AND ", $where);
}

$sqlAcoes = "
    SELECT DISTINCT Acao
    FROM OS_Auditoria
    WHERE Acao IS NOT NULL
    ORDER BY Acao
";

$stmtAcoes = $conn->prepare($sqlAcoes);
$stmtAcoes->execute();

$acoes = $stmtAcoes->fetchAll(PDO::FETCH_COLUMN);

$sqlEntidades = "
    SELECT DISTINCT Entidade
    FROM OS_Auditoria
    WHERE Entidade IS NOT NULL
    ORDER BY Entidade
";

$stmtEntidades = $conn->prepare($sqlEntidades);
$stmtEntidades->execute();

$entidades = $stmtEntidades->fetchAll(PDO::FETCH_COLUMN);

$sql = "
    SELECT TOP 300
        a.AuditoriaId,
        a.EmpresaId,
        a.UsuarioId,
        a.Acao,
        a.Entidade,
        a.EntidadeId,
        a.Descricao,
        a.IpAcesso,
        a.UserAgent,
        a.DataRegistro,

        e.NomeFantasia AS EmpresaNome,
        u.Nome AS UsuarioNome,
        u.Email AS UsuarioEmail
    FROM OS_Auditoria a
    LEFT JOIN OS_Empresas e ON e.EmpresaId = a.EmpresaId
    LEFT JOIN OS_Usuarios u ON u.UsuarioId = a.UsuarioId
    $sqlWhere
    ORDER BY a.AuditoriaId DESC
";

$stmt = $conn->prepare($sql);

foreach ($params as $param => $valor) {
    $stmt->bindValue($param, $valor);
}

$stmt->execute();

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalLogs = count($logs);
$totalLogin = count(array_filter($logs, fn($l) => ($l["Acao"] ?? "") === "LOGIN_SUCESSO"));
$totalAdmin = count(array_filter($logs, fn($l) => str_starts_with(($l["Acao"] ?? ""), "ADMIN_")));
$totalOS = count(array_filter($logs, fn($l) => str_starts_with(($l["Acao"] ?? ""), "OS_")));

function classeAcaoAuditoria($acao)
{
    if (str_starts_with($acao, "LOGIN")) {
        return "bg-success";
    }

    if (str_starts_with($acao, "ADMIN")) {
        return "bg-danger";
    }

    if (str_starts_with($acao, "OS")) {
        return "bg-primary";
    }

    if (str_starts_with($acao, "ANEXO")) {
        return "bg-warning text-dark";
    }

    if (str_starts_with($acao, "USUARIO")) {
        return "bg-dark";
    }

    if (str_starts_with($acao, "CADASTRO")) {
        return "bg-info text-dark";
    }

    return "bg-secondary";
}

function formatarDataAuditoria($data)
{
    if (empty($data)) {
        return "-";
    }

    return date("d/m/Y H:i:s", strtotime($data));
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Auditoria</h3>
            <p>
                Consulte logs de ações sensíveis realizadas na plataforma DirectOS.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="metricas.php" class="btn btn-outline-primary">
                Métricas SaaS
            </a>

            <a href="empresas.php" class="btn btn-outline-primary">
                Empresas
            </a>

            <a href="../dashboard.php" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Logs encontrados</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalLogs ?>
                    </h3>

                    <div class="input-help">
                        Limitado aos 300 mais recentes.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Logins</div>

                    <h3 class="mb-0 mt-2 text-success">
                        <?= (int)$totalLogin ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Ações Admin</div>

                    <h3 class="mb-0 mt-2 text-danger">
                        <?= (int)$totalAdmin ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Ações de OS</div>

                    <h3 class="mb-0 mt-2 text-primary">
                        <?= (int)$totalOS ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card mb-4">
        <div class="card-header">
            Filtros
        </div>

        <div class="card-body">
            <form method="get" action="auditoria.php">

                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Empresa</label>
                        <input 
                            type="text" 
                            name="empresa" 
                            class="form-control"
                            placeholder="Nome da empresa"
                            value="<?= htmlspecialchars($filtroEmpresa) ?>"
                        >
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Usuário</label>
                        <input 
                            type="text" 
                            name="usuario" 
                            class="form-control"
                            placeholder="Nome do usuário"
                            value="<?= htmlspecialchars($filtroUsuario) ?>"
                        >
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Ação</label>

                        <select name="acao" class="form-select">
                            <option value="">Todas</option>

                            <?php foreach ($acoes as $acao): ?>
                                <option 
                                    value="<?= htmlspecialchars($acao) ?>"
                                    <?= $filtroAcao === $acao ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($acao) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Entidade</label>

                        <select name="entidade" class="form-select">
                            <option value="">Todas</option>

                            <?php foreach ($entidades as $entidade): ?>
                                <option 
                                    value="<?= htmlspecialchars($entidade) ?>"
                                    <?= $filtroEntidade === $entidade ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($entidade) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Data início</label>
                        <input 
                            type="date" 
                            name="data_inicio" 
                            class="form-control"
                            value="<?= htmlspecialchars($filtroDataInicio) ?>"
                        >
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Data fim</label>
                        <input 
                            type="date" 
                            name="data_fim" 
                            class="form-control"
                            value="<?= htmlspecialchars($filtroDataFim) ?>"
                        >
                    </div>

                    <div class="col-md-6 mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Filtrar
                        </button>

                        <a href="auditoria.php" class="btn btn-outline-secondary">
                            Limpar
                        </a>
                    </div>

                </div>

            </form>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Registros de auditoria</span>

            <span class="badge bg-primary">
                <?= count($logs) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <?php if (count($logs) === 0): ?>
                <div class="empty-state">
                    Nenhum log encontrado.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora</th>
                                <th>Ação</th>
                                <th>Empresa</th>
                                <th>Usuário</th>
                                <th>Entidade</th>
                                <th>Descrição</th>
                                <th>IP</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            #<?= (int)$log["AuditoriaId"] ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= formatarDataAuditoria($log["DataRegistro"] ?? null) ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= classeAcaoAuditoria($log["Acao"] ?? "") ?>">
                                            <?= htmlspecialchars($log["Acao"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (!empty($log["EmpresaId"])): ?>
                                            <strong>
                                                <?= htmlspecialchars($log["EmpresaNome"] ?? "-") ?>
                                            </strong>

                                            <div class="os-subtitle">
                                                Empresa #<?= (int)$log["EmpresaId"] ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sem empresa</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($log["UsuarioId"])): ?>
                                            <strong>
                                                <?= htmlspecialchars($log["UsuarioNome"] ?? "-") ?>
                                            </strong>

                                            <div class="os-subtitle">
                                                <?= htmlspecialchars($log["UsuarioEmail"] ?? "") ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sistema / público</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div>
                                            <strong>
                                                <?= htmlspecialchars($log["Entidade"] ?? "-") ?>
                                            </strong>
                                        </div>

                                        <?php if (!empty($log["EntidadeId"])): ?>
                                            <div class="os-subtitle">
                                                ID <?= (int)$log["EntidadeId"] ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($log["Descricao"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <span class="text-muted">
                                            <?= htmlspecialchars($log["IpAcesso"] ?? "-") ?>
                                        </span>
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

<?php require_once "../includes/footer.php"; ?>