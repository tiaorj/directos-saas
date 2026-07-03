<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["SuperAdmin"]);

$sql = "
    SELECT
        p.PlanoId,
        p.Nome,
        p.Slug,
        p.Descricao,
        p.LimiteOSMes,
        p.LimiteUsuarios,
        p.PermiteAnexos,
        p.PermiteAreaCliente,
        p.PermiteWhatsapp,
        p.ValorMensal,
        p.Ativo,
        p.DataCadastro,

        (
            SELECT COUNT(*)
            FROM OS_Empresas e
            WHERE e.PlanoId = p.PlanoId
              AND e.Ativo = 1
        ) AS EmpresasAtivas,

        (
            SELECT COUNT(*)
            FROM OS_Assinaturas a
            INNER JOIN OS_Empresas e ON e.EmpresaId = a.EmpresaId
            WHERE a.PlanoId = p.PlanoId
              AND a.Status = 'Ativa'
              AND e.Ativo = 1
        ) AS AssinaturasAtivas
    FROM OS_Planos p
    ORDER BY
        p.Ativo DESC,
        p.ValorMensal ASC,
        p.Nome ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$planos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPlanos = count($planos);
$totalPlanosAtivos = count(array_filter($planos, fn($plano) => (int)$plano["Ativo"] === 1));
$totalEmpresasAtivas = array_sum(array_map(fn($plano) => (int)$plano["EmpresasAtivas"], $planos));
$receitaPotencial = 0;

foreach ($planos as $plano) {
    if ((int)$plano["Ativo"] === 1) {
        $receitaPotencial += ((float)$plano["ValorMensal"] * (int)$plano["EmpresasAtivas"]);
    }
}

$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";

function formatarLimitePlanoAdmin($valor)
{
    if ($valor === null || $valor === "") {
        return "Ilimitado";
    }

    return (string)(int)$valor;
}

function badgeRecursoPlano($habilitado, $texto)
{
    $classe = (int)$habilitado === 1 ? "bg-success" : "bg-secondary";
    $rotulo = (int)$habilitado === 1 ? $texto : $texto . " off";

    return '<span class="badge ' . $classe . ' me-1 mb-1">' . htmlspecialchars($rotulo) . '</span>';
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Planos</h3>
            <p>
                Gerencie os planos comerciais, limites e recursos disponíveis na plataforma DirectOS.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="plano_editar.php" class="btn btn-primary">
                Novo plano
            </a>

            <a href="metricas.php" class="btn btn-outline-primary">
                Métricas SaaS
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

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Planos cadastrados</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalPlanos ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Planos ativos</div>

                    <h3 class="mb-0 mt-2 text-success">
                        <?= (int)$totalPlanosAtivos ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresas ativas em planos</div>

                    <h3 class="mb-0 mt-2 text-primary">
                        <?= (int)$totalEmpresasAtivas ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Receita potencial mensal</div>

                    <h3 class="mb-0 mt-2 text-primary">
                        R$ <?= number_format($receitaPotencial, 2, ",", ".") ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Planos cadastrados</span>

            <span class="badge bg-primary">
                <?= (int)$totalPlanos ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <?php if (count($planos) === 0): ?>
                <div class="empty-state">
                    Nenhum plano cadastrado até o momento.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Plano</th>
                                <th>Valor</th>
                                <th>Limites</th>
                                <th>Recursos</th>
                                <th>Status</th>
                                <th>Empresas</th>
                                <th width="120">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($planos as $plano): ?>
                                <tr>
                                    <td>
                                        <strong>#<?= (int)$plano["PlanoId"] ?></strong>
                                    </td>

                                    <td>
                                        <strong><?= htmlspecialchars($plano["Nome"] ?? "-") ?></strong>

                                        <div class="os-subtitle">
                                            slug: <?= htmlspecialchars($plano["Slug"] ?? "-") ?>
                                        </div>

                                        <?php if (!empty($plano["Descricao"])): ?>
                                            <div class="os-subtitle">
                                                <?= htmlspecialchars($plano["Descricao"]) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong>
                                            R$ <?= number_format((float)$plano["ValorMensal"], 2, ",", ".") ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            por mês
                                        </div>
                                    </td>

                                    <td>
                                        <div>
                                            <strong>OS/mês:</strong>
                                            <?= htmlspecialchars(formatarLimitePlanoAdmin($plano["LimiteOSMes"])) ?>
                                        </div>

                                        <div class="os-subtitle">
                                            Usuários: <?= htmlspecialchars(formatarLimitePlanoAdmin($plano["LimiteUsuarios"])) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= badgeRecursoPlano($plano["PermiteAnexos"], "Anexos") ?>
                                        <?= badgeRecursoPlano($plano["PermiteAreaCliente"], "Área cliente") ?>
                                        <?= badgeRecursoPlano($plano["PermiteWhatsapp"], "WhatsApp") ?>
                                    </td>

                                    <td>
                                        <?php if ((int)$plano["Ativo"] === 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong><?= (int)$plano["EmpresasAtivas"] ?></strong>

                                        <div class="os-subtitle">
                                            <?= (int)$plano["AssinaturasAtivas"] ?> assinatura(s)
                                        </div>
                                    </td>

                                    <td>
                                        <a
                                            href="plano_editar.php?id=<?= (int)$plano["PlanoId"] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Editar
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
