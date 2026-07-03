<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

exigirPerfil(["SuperAdmin"]);

$filtroStatus = trim($_GET["status"] ?? "Pendente");
$filtroEmpresa = trim($_GET["empresa"] ?? "");

$where = [];
$params = [];

if ($filtroStatus !== "") {
    $where[] = "s.Status = :Status";
    $params[":Status"] = $filtroStatus;
}

if ($filtroEmpresa !== "") {
    $where[] = "e.NomeFantasia LIKE :Empresa";
    $params[":Empresa"] = "%" . $filtroEmpresa . "%";
}

$sqlWhere = "";

if (count($where) > 0) {
    $sqlWhere = "WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        s.SolicitacaoId,
        s.EmpresaId,
        s.PlanoAtualId,
        s.PlanoSolicitadoId,
        s.UsuarioId,
        s.Status,
        s.Mensagem,
        s.ObservacaoAdmin,
        s.DataSolicitacao,
        s.DataResposta,

        e.NomeFantasia AS EmpresaNome,
        e.Email AS EmpresaEmail,
        e.StatusComercial,
        e.Ativo AS EmpresaAtiva,

        u.Nome AS UsuarioNome,
        u.Email AS UsuarioEmail,

        pa.Nome AS PlanoAtualNome,
        pa.ValorMensal AS PlanoAtualValor,

        ps.Nome AS PlanoSolicitadoNome,
        ps.ValorMensal AS PlanoSolicitadoValor,
        ps.LimiteOSMes AS PlanoSolicitadoLimiteOS,
        ps.LimiteUsuarios AS PlanoSolicitadoLimiteUsuarios
    FROM OS_SolicitacoesPlano s
    INNER JOIN OS_Empresas e ON e.EmpresaId = s.EmpresaId
    LEFT JOIN OS_Usuarios u ON u.UsuarioId = s.UsuarioId
    LEFT JOIN OS_Planos pa ON pa.PlanoId = s.PlanoAtualId
    INNER JOIN OS_Planos ps ON ps.PlanoId = s.PlanoSolicitadoId
    $sqlWhere
    ORDER BY
        CASE WHEN s.Status = 'Pendente' THEN 0 ELSE 1 END,
        s.DataSolicitacao DESC
";

$stmt = $conn->prepare($sql);

foreach ($params as $param => $valor) {
    $stmt->bindValue($param, $valor);
}

$stmt->execute();

$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPendentes = (int)$conn->query("SELECT COUNT(*) FROM OS_SolicitacoesPlano WHERE Status = 'Pendente'")->fetchColumn();
$totalAprovadas = (int)$conn->query("SELECT COUNT(*) FROM OS_SolicitacoesPlano WHERE Status = 'Aprovada'")->fetchColumn();
$totalRecusadas = (int)$conn->query("SELECT COUNT(*) FROM OS_SolicitacoesPlano WHERE Status = 'Recusada'")->fetchColumn();

$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";

function formatarDataSolicitacaoPlano($data)
{
    if (empty($data)) {
        return "-";
    }

    return date("d/m/Y H:i", strtotime($data));
}

function badgeStatusSolicitacaoPlano($status)
{
    if ($status === "Pendente") {
        return '<span class="badge bg-warning text-dark">Pendente</span>';
    }

    if ($status === "Aprovada") {
        return '<span class="badge bg-success">Aprovada</span>';
    }

    if ($status === "Recusada") {
        return '<span class="badge bg-danger">Recusada</span>';
    }

    if ($status === "Cancelada") {
        return '<span class="badge bg-secondary">Cancelada</span>';
    }

    return '<span class="badge bg-dark">' . htmlspecialchars($status) . '</span>';
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Solicitações de plano</h3>
            <p>
                Analise pedidos de upgrade ou downgrade feitos pelas empresas no DirectOS.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="planos.php" class="btn btn-outline-primary">
                Planos
            </a>
            <a href="assinaturas.php" class="btn btn-outline-primary">
                Assinaturas
            </a>
            <a href="empresas.php" class="btn btn-outline-secondary">
                Empresas
            </a>
        </div>
    </div>

    <?php if ($sucesso !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Pendentes</div>
                    <h3 class="mb-0 mt-2 text-warning"><?= (int)$totalPendentes ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Aprovadas</div>
                    <h3 class="mb-0 mt-2 text-success"><?= (int)$totalAprovadas ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Recusadas</div>
                    <h3 class="mb-0 mt-2 text-danger"><?= (int)$totalRecusadas ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card form-card mb-4">
        <div class="card-header">Filtros</div>
        <div class="card-body">
            <form method="get" action="solicitacoes_planos.php">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Empresa</label>
                        <input
                            type="text"
                            name="empresa"
                            class="form-control"
                            value="<?= htmlspecialchars($filtroEmpresa) ?>"
                            placeholder="Nome da empresa"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="" <?= $filtroStatus === "" ? "selected" : "" ?>>Todos</option>
                            <option value="Pendente" <?= $filtroStatus === "Pendente" ? "selected" : "" ?>>Pendente</option>
                            <option value="Aprovada" <?= $filtroStatus === "Aprovada" ? "selected" : "" ?>>Aprovada</option>
                            <option value="Recusada" <?= $filtroStatus === "Recusada" ? "selected" : "" ?>>Recusada</option>
                            <option value="Cancelada" <?= $filtroStatus === "Cancelada" ? "selected" : "" ?>>Cancelada</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="solicitacoes_planos.php" class="btn btn-outline-secondary">Limpar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Solicitações encontradas</span>
            <span class="badge bg-primary"><?= count($solicitacoes) ?> registro(s)</span>
        </div>

        <div class="card-body p-0">
            <?php if (count($solicitacoes) === 0): ?>
                <div class="empty-state">
                    Nenhuma solicitação encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa</th>
                                <th>Plano atual</th>
                                <th>Plano solicitado</th>
                                <th>Status</th>
                                <th>Solicitação</th>
                                <th width="280">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitacoes as $solicitacao): ?>
                                <tr>
                                    <td><strong>#<?= (int)$solicitacao["SolicitacaoId"] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($solicitacao["EmpresaNome"] ?? "-") ?></strong>
                                        <div class="os-subtitle"><?= htmlspecialchars($solicitacao["EmpresaEmail"] ?? "") ?></div>
                                        <?php if (!empty($solicitacao["UsuarioNome"])): ?>
                                            <div class="os-subtitle">
                                                Solicitado por: <?= htmlspecialchars($solicitacao["UsuarioNome"]) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($solicitacao["PlanoAtualNome"] ?? "Sem plano") ?>
                                        <div class="os-subtitle">
                                            R$ <?= number_format((float)($solicitacao["PlanoAtualValor"] ?? 0), 2, ",", ".") ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($solicitacao["PlanoSolicitadoNome"] ?? "-") ?></strong>
                                        <div class="os-subtitle">
                                            R$ <?= number_format((float)$solicitacao["PlanoSolicitadoValor"], 2, ",", ".") ?>
                                        </div>
                                    </td>
                                    <td><?= badgeStatusSolicitacaoPlano($solicitacao["Status"] ?? "-") ?></td>
                                    <td>
                                        <?= formatarDataSolicitacaoPlano($solicitacao["DataSolicitacao"] ?? null) ?>
                                        <?php if (!empty($solicitacao["Mensagem"])): ?>
                                            <div class="os-subtitle mt-1">
                                                <?= htmlspecialchars($solicitacao["Mensagem"]) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($solicitacao["ObservacaoAdmin"])): ?>
                                            <div class="os-subtitle mt-1">
                                                Admin: <?= htmlspecialchars($solicitacao["ObservacaoAdmin"]) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($solicitacao["Status"] ?? "") === "Pendente"): ?>
                                            <form method="post" action="solicitacao_plano_processar.php" class="mb-2">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="SolicitacaoId" value="<?= (int)$solicitacao["SolicitacaoId"] ?>">
                                                <input type="hidden" name="Acao" value="aprovar">
                                                <textarea
                                                    name="ObservacaoAdmin"
                                                    class="form-control form-control-sm mb-2"
                                                    rows="2"
                                                    placeholder="Observação interna opcional"
                                                ></textarea>
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-success w-100"
                                                    onclick="return confirm('Aprovar esta alteração de plano?')"
                                                >
                                                    Aprovar alteração
                                                </button>
                                            </form>

                                            <form method="post" action="solicitacao_plano_processar.php">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="SolicitacaoId" value="<?= (int)$solicitacao["SolicitacaoId"] ?>">
                                                <input type="hidden" name="Acao" value="recusar">
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger w-100"
                                                    onclick="return confirm('Recusar esta solicitação?')"
                                                >
                                                    Recusar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Processada</span>
                                        <?php endif; ?>
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
