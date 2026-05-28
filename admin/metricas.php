<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["SuperAdmin"]);

function buscarValorAdmin($conn, $sql)
{
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    return $stmt->fetchColumn();
}

$mrr = buscarValorAdmin($conn, "
    SELECT ISNULL(SUM(p.ValorMensal), 0)
    FROM OS_Assinaturas a
    INNER JOIN OS_Planos p ON p.PlanoId = a.PlanoId
    INNER JOIN OS_Empresas e ON e.EmpresaId = a.EmpresaId
    WHERE a.Status = 'Ativa'
      AND e.Ativo = 1
");

$totalEmpresas = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_Empresas
");

$totalEmpresasAtivas = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_Empresas
    WHERE Ativo = 1
");

$totalEmpresasInativas = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_Empresas
    WHERE Ativo = 0
");

$novasEmpresasMes = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_Empresas
    WHERE DataCadastro >= DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)
      AND DataCadastro < DATEADD(MONTH, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
");

$totalUsuariosAtivos = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_Usuarios u
    INNER JOIN OS_Empresas e ON e.EmpresaId = u.EmpresaId
    WHERE u.Ativo = 1
      AND e.Ativo = 1
");

$totalClientesAtivos = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_Clientes c
    INNER JOIN OS_Empresas e ON e.EmpresaId = c.EmpresaId
    WHERE c.Ativo = 1
      AND e.Ativo = 1
");

$totalOSMes = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_OrdensServico os
    INNER JOIN OS_Empresas e ON e.EmpresaId = os.EmpresaId
    WHERE e.Ativo = 1
      AND os.DataAbertura >= DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)
      AND os.DataAbertura < DATEADD(MONTH, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
");

$totalOSGeral = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_OrdensServico os
    INNER JOIN OS_Empresas e ON e.EmpresaId = os.EmpresaId
    WHERE e.Ativo = 1
");

$assinaturasAtivas = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_Assinaturas a
    INNER JOIN OS_Empresas e ON e.EmpresaId = a.EmpresaId
    WHERE a.Status = 'Ativa'
      AND e.Ativo = 1
");

$assinaturasCanceladasMes = buscarValorAdmin($conn, "
    SELECT COUNT(*)
    FROM OS_Assinaturas
    WHERE Status = 'Cancelada'
      AND DataFim >= DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)
      AND DataFim < DATEADD(MONTH, 1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
");

$sqlPlanos = "
    SELECT
        p.PlanoId,
        p.Nome,
        p.ValorMensal,
        COUNT(a.AssinaturaId) AS TotalAssinaturas,
        ISNULL(SUM(p.ValorMensal), 0) AS ReceitaMensal
    FROM OS_Planos p
    LEFT JOIN OS_Assinaturas a 
        ON a.PlanoId = p.PlanoId
       AND a.Status = 'Ativa'
    LEFT JOIN OS_Empresas e 
        ON e.EmpresaId = a.EmpresaId
       AND e.Ativo = 1
    WHERE p.Ativo = 1
    GROUP BY
        p.PlanoId,
        p.Nome,
        p.ValorMensal
    ORDER BY p.ValorMensal
";

$stmtPlanos = $conn->prepare($sqlPlanos);
$stmtPlanos->execute();

$metricasPlanos = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);

$sqlNovasEmpresas = "
    SELECT TOP 10
        e.EmpresaId,
        e.NomeFantasia,
        e.Email,
        e.Ativo,
        e.DataCadastro,
        ISNULL(p.Nome, 'Sem plano') AS PlanoAtual
    FROM OS_Empresas e
    OUTER APPLY (
        SELECT TOP 1
            p.Nome
        FROM OS_Assinaturas a
        INNER JOIN OS_Planos p ON p.PlanoId = a.PlanoId
        WHERE a.EmpresaId = e.EmpresaId
          AND a.Status = 'Ativa'
        ORDER BY a.AssinaturaId DESC
    ) p
    ORDER BY e.EmpresaId DESC
";

$stmtNovasEmpresas = $conn->prepare($sqlNovasEmpresas);
$stmtNovasEmpresas->execute();

$novasEmpresas = $stmtNovasEmpresas->fetchAll(PDO::FETCH_ASSOC);

$sqlOSPorStatus = "
    SELECT
        os.Status,
        COUNT(*) AS Total
    FROM OS_OrdensServico os
    INNER JOIN OS_Empresas e ON e.EmpresaId = os.EmpresaId
    WHERE e.Ativo = 1
    GROUP BY os.Status
    ORDER BY Total DESC
";

$stmtOSPorStatus = $conn->prepare($sqlOSPorStatus);
$stmtOSPorStatus->execute();

$osPorStatus = $stmtOSPorStatus->fetchAll(PDO::FETCH_ASSOC);

$ticketMedio = (int)$assinaturasAtivas > 0 
    ? ((float)$mrr / (int)$assinaturasAtivas) 
    : 0;

$percentualEmpresasAtivas = (int)$totalEmpresas > 0
    ? round(((int)$totalEmpresasAtivas / (int)$totalEmpresas) * 100)
    : 0;

function formatarMoedaAdmin($valor)
{
    return "R$ " . number_format((float)$valor, 2, ",", ".");
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Métricas SaaS</h3>
            <p>
                Acompanhe indicadores da plataforma, receita mensal recorrente e crescimento das empresas.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="empresas.php" class="btn btn-outline-primary">
                Empresas
            </a>

            <a href="assinaturas.php" class="btn btn-outline-primary">
                Assinaturas
            </a>
            <a href="auditoria.php" class="btn btn-outline-primary">
                Auditoria
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
                    <div class="small text-muted">MRR</div>

                    <h3 class="mb-1 mt-2 text-primary">
                        <?= formatarMoedaAdmin($mrr) ?>
                    </h3>

                    <div class="input-help">
                        Receita mensal recorrente ativa.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Ticket médio</div>

                    <h3 class="mb-1 mt-2">
                        <?= formatarMoedaAdmin($ticketMedio) ?>
                    </h3>

                    <div class="input-help">
                        MRR dividido por assinaturas ativas.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresas ativas</div>

                    <h3 class="mb-1 mt-2 text-success">
                        <?= (int)$totalEmpresasAtivas ?>
                    </h3>

                    <div class="input-help">
                        <?= (int)$percentualEmpresasAtivas ?>% das empresas cadastradas.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Novas empresas no mês</div>

                    <h3 class="mb-1 mt-2">
                        <?= (int)$novasEmpresasMes ?>
                    </h3>

                    <div class="input-help">
                        Cadastros realizados no mês atual.
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Assinaturas ativas</div>

                    <h3 class="mb-1 mt-2 text-success">
                        <?= (int)$assinaturasAtivas ?>
                    </h3>

                    <a href="assinaturas.php?status=Ativa" class="small text-decoration-none">
                        Ver assinaturas ativas
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Cancelamentos no mês</div>

                    <h3 class="mb-1 mt-2 text-secondary">
                        <?= (int)$assinaturasCanceladasMes ?>
                    </h3>

                    <a href="assinaturas.php?status=Cancelada" class="small text-decoration-none">
                        Ver canceladas
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">OS no mês</div>

                    <h3 class="mb-1 mt-2 text-primary">
                        <?= (int)$totalOSMes ?>
                    </h3>

                    <div class="input-help">
                        OS abertas no mês atual.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">OS totais</div>

                    <h3 class="mb-1 mt-2">
                        <?= (int)$totalOSGeral ?>
                    </h3>

                    <div class="input-help">
                        Total em empresas ativas.
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Receita por plano
                </div>

                <div class="card-body p-0">

                    <?php if (count($metricasPlanos) === 0): ?>
                        <div class="empty-state">
                            Nenhum plano encontrado.
                        </div>
                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-os mb-0">
                                <thead>
                                    <tr>
                                        <th>Plano</th>
                                        <th>Valor</th>
                                        <th>Assinaturas</th>
                                        <th>MRR</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($metricasPlanos as $plano): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= htmlspecialchars($plano["Nome"] ?? "-") ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= formatarMoedaAdmin($plano["ValorMensal"]) ?>
                                            </td>

                                            <td>
                                                <strong><?= (int)$plano["TotalAssinaturas"] ?></strong>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= formatarMoedaAdmin($plano["ReceitaMensal"]) ?>
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

        <div class="col-md-6">
            <div class="card form-card h-100">
                <div class="card-header">
                    Ordens de Serviço por status
                </div>

                <div class="card-body p-0">

                    <?php if (count($osPorStatus) === 0): ?>
                        <div class="empty-state">
                            Nenhuma OS encontrada.
                        </div>
                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-os mb-0">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($osPorStatus as $status): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($status["Status"] ?? "-") ?>
                                                </span>
                                            </td>

                                            <td>
                                                <strong><?= (int)$status["Total"] ?></strong>
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

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Usuários ativos</div>

                    <h3 class="mb-1 mt-2">
                        <?= (int)$totalUsuariosAtivos ?>
                    </h3>

                    <div class="input-help">
                        Usuários ativos em empresas ativas.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Clientes ativos</div>

                    <h3 class="mb-1 mt-2">
                        <?= (int)$totalClientesAtivos ?>
                    </h3>

                    <div class="input-help">
                        Clientes ativos em empresas ativas.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresas inativas</div>

                    <h3 class="mb-1 mt-2 text-secondary">
                        <?= (int)$totalEmpresasInativas ?>
                    </h3>

                    <a href="empresas.php" class="small text-decoration-none">
                        Ver empresas
                    </a>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Últimas empresas cadastradas</span>

            <a href="empresas.php" class="btn btn-sm btn-outline-primary">
                Ver todas
            </a>
        </div>

        <div class="card-body p-0">

            <?php if (count($novasEmpresas) === 0): ?>
                <div class="empty-state">
                    Nenhuma empresa cadastrada.
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
                                <th>Cadastro</th>
                                <th width="100">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($novasEmpresas as $empresa): ?>
                                <tr>
                                    <td>
                                        <strong>#<?= (int)$empresa["EmpresaId"] ?></strong>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($empresa["NomeFantasia"] ?? "-") ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            <?= htmlspecialchars($empresa["Email"] ?? "") ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($empresa["PlanoAtual"] ?? "Sem plano") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ((int)$empresa["Ativo"] === 1): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= !empty($empresa["DataCadastro"])
                                            ? date("d/m/Y", strtotime($empresa["DataCadastro"]))
                                            : "-"
                                        ?>
                                    </td>

                                    <td>
                                        <a 
                                            href="empresa.php?id=<?= (int)$empresa["EmpresaId"] ?>" 
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

            <?php endif; ?>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>