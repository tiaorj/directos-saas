<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";

exigirPerfil(["SuperAdmin"]);

$sql = "
    SELECT
        e.EmpresaId,
        e.NomeFantasia,
        e.RazaoSocial,
        e.Cnpj,
        e.Email,
        e.Telefone,
        e.WhatsApp,
        e.Slug,
        e.Ativo,
        e.DataCadastro,

        ISNULL(p.Nome, 'Sem plano') AS PlanoAtual,

        (
            SELECT COUNT(*)
            FROM OS_Usuarios u
            WHERE u.EmpresaId = e.EmpresaId
              AND u.Ativo = 1
        ) AS TotalUsuarios,

        (
            SELECT COUNT(*)
            FROM OS_Clientes c
            WHERE c.EmpresaId = e.EmpresaId
              AND c.Ativo = 1
        ) AS TotalClientes,

        (
            SELECT COUNT(*)
            FROM OS_Servicos s
            WHERE s.EmpresaId = e.EmpresaId
              AND s.Ativo = 1
        ) AS TotalServicos,

        (
            SELECT COUNT(*)
            FROM OS_OrdensServico os
            WHERE os.EmpresaId = e.EmpresaId
        ) AS TotalOS,

        (
            SELECT COUNT(*)
            FROM OS_OrdensServico os
            WHERE os.EmpresaId = e.EmpresaId
              AND os.Status NOT IN ('Concluída', 'Cancelada')
        ) AS TotalOSAtivas

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

$stmt = $conn->prepare($sql);
$stmt->execute();

$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Admin SaaS</h3>
            <p>
                Acompanhe todas as empresas cadastradas na plataforma DirectOS.
            </p>
        </div>

        <?php if ($usuarioPerfil === "SuperAdmin"): ?>
            <div class="sidebar-section">Plataforma</div>

            <div class="d-flex gap-2">
                <a href="metricas.php" class="btn btn-primary">
                    Métricas SaaS
                </a>

                <a href="assinaturas.php" class="btn btn-outline-primary">
                    Ver Assinaturas
                </a>

                <a href="../dashboard.php" class="btn btn-outline-secondary">
                    Voltar
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresas cadastradas</div>

                    <h3 class="mb-0 mt-2">
                        <?= count($empresas) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresas ativas</div>

                    <h3 class="mb-0 mt-2 text-success">
                        <?= count(array_filter($empresas, fn($e) => (int)$e["Ativo"] === 1)) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresas inativas</div>

                    <h3 class="mb-0 mt-2 text-secondary">
                        <?= count(array_filter($empresas, fn($e) => (int)$e["Ativo"] === 0)) ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Total de OS</div>

                    <h3 class="mb-0 mt-2 text-primary">
                        <?= array_sum(array_map(fn($e) => (int)$e["TotalOS"], $empresas)) ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Empresas cadastradas</span>

            <span class="badge bg-primary">
                <?= count($empresas) ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">

            <?php if (count($empresas) === 0): ?>
                <div class="empty-state">
                    Nenhuma empresa cadastrada até o momento.
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
                                <th>Usuários</th>
                                <th>Clientes</th>
                                <th>Serviços</th>
                                <th>OS</th>
                                <th>Cadastro</th>
                                <th width="120">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($empresas as $empresa): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            #<?= (int)$empresa["EmpresaId"] ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($empresa["NomeFantasia"] ?? "-") ?>
                                        </strong>

                                        <div class="os-subtitle">
                                            <?= htmlspecialchars($empresa["Email"] ?? "") ?>
                                        </div>

                                        <?php if (!empty($empresa["Slug"])): ?>
                                            <div class="os-subtitle">
                                                slug: <?= htmlspecialchars($empresa["Slug"]) ?>
                                            </div>
                                        <?php endif; ?>
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
                                        <?= (int)$empresa["TotalUsuarios"] ?>
                                    </td>

                                    <td>
                                        <?= (int)$empresa["TotalClientes"] ?>
                                    </td>

                                    <td>
                                        <?= (int)$empresa["TotalServicos"] ?>
                                    </td>

                                    <td>
                                        <strong><?= (int)$empresa["TotalOS"] ?></strong>

                                        <div class="os-subtitle">
                                            <?= (int)$empresa["TotalOSAtivas"] ?> ativa(s)
                                        </div>
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