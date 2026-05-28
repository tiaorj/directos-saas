<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["SuperAdmin"]);

$filtroEmpresa = trim($_GET["empresa"] ?? "");
$filtroPlano = (int)($_GET["plano"] ?? 0);
$filtroStatus = trim($_GET["status"] ?? "");

$sqlPlanos = "
    SELECT
        PlanoId,
        Nome
    FROM OS_Planos
    WHERE Ativo = 1
    ORDER BY ValorMensal
";

$stmtPlanos = $conn->prepare($sqlPlanos);
$stmtPlanos->execute();

$planos = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);

$where = [];
$params = [];

if ($filtroEmpresa !== "") {
    $where[] = "e.NomeFantasia LIKE :Empresa";
    $params[":Empresa"] = "%" . $filtroEmpresa . "%";
}

if ($filtroPlano > 0) {
    $where[] = "p.PlanoId = :PlanoId";
    $params[":PlanoId"] = $filtroPlano;
}

if ($filtroStatus !== "") {
    $where[] = "a.Status = :Status";
    $params[":Status"] = $filtroStatus;
}

$sqlWhere = "";

if (count($where) > 0) {
    $sqlWhere = "WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        a.AssinaturaId,
        a.EmpresaId,
        a.PlanoId,
        a.Status,
        a.DataInicio,
        a.DataFim,

        e.NomeFantasia,
        e.Email AS EmpresaEmail,
        e.Ativo AS EmpresaAtiva,

        p.Nome AS PlanoNome,
        p.ValorMensal,
        p.LimiteOSMes,
        p.LimiteUsuarios
    FROM OS_Assinaturas a
    INNER JOIN OS_Empresas e ON e.EmpresaId = a.EmpresaId
    INNER JOIN OS_Planos p ON p.PlanoId = a.PlanoId
    $sqlWhere
    ORDER BY a.AssinaturaId DESC
";

$stmt = $conn->prepare($sql);

foreach ($params as $param => $valor) {
    if ($param === ":PlanoId") {
        $stmt->bindValue($param, $valor, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($param, $valor);
    }
}

$stmt->execute();

$assinaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAssinaturas = count($assinaturas);
$totalAtivas = count(array_filter($assinaturas, fn($a) => $a["Status"] === "Ativa"));
$totalCanceladas = count(array_filter($assinaturas, fn($a) => $a["Status"] === "Cancelada"));

$valorMensalAtivo = 0;

foreach ($assinaturas as $assinatura) {
    if ($assinatura["Status"] === "Ativa") {
        $valorMensalAtivo += (float)$assinatura["ValorMensal"];
    }
}

function formatarDataAssinatura($data, $comHora = false)
{
    if (empty($data)) {
        return "-";
    }

    return $comHora
        ? date("d/m/Y H:i", strtotime($data))
        : date("d/m/Y", strtotime($data));
}

function formatarLimiteAssinatura($valor)
{
    if ($valor === null || $valor === "") {
        return "Ilimitado";
    }

    return (string)(int)$valor;
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Assinaturas</h3>
            <p>
                Visualize o histórico de planos e assinaturas das empresas cadastradas.
            </p>
        </div>

        <a href="empresas.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Registros encontrados</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalAssinaturas ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Assinaturas ativas</div>

                    <h3 class="mb-0 mt-2 text-success">
                        <?= (int)$totalAtivas ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Assinaturas canceladas</div>

                    <h3 class="mb-0 mt-2 text-secondary">
                        <?= (int)$totalCanceladas ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Receita mensal ativa</div>

                    <h3 class="mb-0 mt-2 text-primary">
                        R$ <?= number_format($valorMensalAtivo, 2, ",", ".") ?>
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
            <form method="get" action="assinaturas.php">

                <div class="row">

                    <div class="col-md-4 mb-3">
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
                        <label class="form-label">Plano</label>

                        <select name="plano" class="form-select">
                            <option value="0">Todos</option>

                            <?php foreach ($planos as $plano): ?>
                                <option 
                                    value="<?= (int)$plano["PlanoId"] ?>"
                                    <?= $filtroPlano === (int)$plano["PlanoId"] ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($plano["Nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>

                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="Ativa" <?= $filtroStatus === "Ativa" ? "selected" : "" ?>>Ativa</option>
                            <option value="Cancelada" <?= $filtroStatus === "Cancelada" ? "selected" : "" ?>>Cancelada</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Filtrar
                        </button>

                        <a href="assinaturas.php" class="btn btn-outline-secondary">
                            Limpar
                        </a>
                    </div>

                </div>

            </form>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Histórico de assinaturas</span>

            <span class="badge bg-primary">
                <?= count($assinaturas) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <?php if (count($assinaturas) === 0): ?>
                <div class="empty-state">
                    Nenhuma assinatura encontrada.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa</th>
                                <th>Plano</th>
                                <th>Status</th>
                                <th>Valor</th>
                                <th>Limites</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th width="110">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($assinaturas as $assinatura): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            #<?= (int)$assinatura["AssinaturaId"] ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($assinatura["NomeFantasia"] ?? "-") ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            <?= htmlspecialchars($assinatura["EmpresaEmail"] ?? "") ?>
                                        </div>

                                        <?php if ((int)$assinatura["EmpresaAtiva"] === 1): ?>
                                            <span class="badge bg-success mt-1">Empresa ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mt-1">Empresa inativa</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($assinatura["PlanoNome"] ?? "-") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ($assinatura["Status"] === "Ativa"): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php elseif ($assinatura["Status"] === "Cancelada"): ?>
                                            <span class="badge bg-secondary">Cancelada</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark">
                                                <?= htmlspecialchars($assinatura["Status"] ?? "-") ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong>
                                            R$ <?= number_format((float)$assinatura["ValorMensal"], 2, ",", ".") ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <div class="small">
                                            OS/mês:
                                            <strong>
                                                <?= htmlspecialchars(formatarLimiteAssinatura($assinatura["LimiteOSMes"])) ?>
                                            </strong>
                                        </div>

                                        <div class="small">
                                            Usuários:
                                            <strong>
                                                <?= htmlspecialchars(formatarLimiteAssinatura($assinatura["LimiteUsuarios"])) ?>
                                            </strong>
                                        </div>
                                    </td>

                                    <td>
                                        <?= formatarDataAssinatura($assinatura["DataInicio"], true) ?>
                                    </td>

                                    <td>
                                        <?= formatarDataAssinatura($assinatura["DataFim"], true) ?>
                                    </td>

                                    <td>
                                        <a 
                                            href="empresa.php?id=<?= (int)$assinatura["EmpresaId"] ?>" 
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Empresa
                                        </a>
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