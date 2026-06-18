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
        e.Email,
        e.Telefone,
        e.WhatsApp,
        e.Slug,
        e.Ativo,
        e.Segmento,
        e.StatusComercial,
        e.DataInicioTeste,
        e.DataFimTeste,
        e.ObservacaoComercial,
        p.Nome AS PlanoNome,
        p.Slug AS PlanoSlug,
        u.Nome AS ResponsavelNome,
        u.Email AS ResponsavelEmail
    FROM OS_Empresas e
    LEFT JOIN OS_Planos p ON p.PlanoId = e.PlanoId
    OUTER APPLY (
        SELECT TOP 1
            u.Nome,
            u.Email
        FROM OS_Usuarios u
        WHERE u.EmpresaId = e.EmpresaId
          AND u.Perfil = 'Admin'
        ORDER BY u.UsuarioId ASC
    ) u
    WHERE e.DataInicioTeste IS NOT NULL
       OR e.StatusComercial = 'Teste'
       OR p.Slug = 'teste-assistido'
    ORDER BY e.EmpresaId DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$implantacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalImplantacoes = count($implantacoes);
$totalAtivas = count(array_filter($implantacoes, fn($i) => (int)$i["Ativo"] === 1));
$hoje = strtotime(date("Y-m-d"));
$totalVencidas = count(array_filter($implantacoes, function ($i) use ($hoje) {
    if (empty($i["DataFimTeste"])) {
        return false;
    }

    return strtotime($i["DataFimTeste"]) < $hoje;
}));

$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";

function formatarDataImplantacao($data)
{
    if (empty($data)) {
        return "-";
    }

    return date("d/m/Y", strtotime($data));
}

function classeStatusImplantacao($implantacao)
{
    if ((int)$implantacao["Ativo"] !== 1) {
        return "bg-secondary";
    }

    if (!empty($implantacao["DataFimTeste"]) && strtotime($implantacao["DataFimTeste"]) < strtotime(date("Y-m-d"))) {
        return "bg-warning text-dark";
    }

    return "bg-success";
}

function textoStatusImplantacao($implantacao)
{
    if ((int)$implantacao["Ativo"] !== 1) {
        return "Inativa";
    }

    if (!empty($implantacao["DataFimTeste"]) && strtotime($implantacao["DataFimTeste"]) < strtotime(date("Y-m-d"))) {
        return "Teste vencido";
    }

    return $implantacao["StatusComercial"] ?: "Ativa";
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Implantação Assistida</h3>
            <p>
                Libere acessos de teste para interessados e acompanhe empresas em avaliação inicial.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="implantacao_nova.php" class="btn btn-primary">
                Nova implantação
            </a>

            <a href="empresas.php" class="btn btn-outline-primary">
                Empresas
            </a>

            <a href="../dashboard.php" class="btn btn-outline-secondary">
                Voltar
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
                    <div class="small text-muted">Implantações</div>
                    <h3 class="mb-0 mt-2"><?= (int)$totalImplantacoes ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresas ativas</div>
                    <h3 class="mb-0 mt-2 text-success"><?= (int)$totalAtivas ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Testes vencidos</div>
                    <h3 class="mb-0 mt-2 text-warning"><?= (int)$totalVencidas ?></h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Empresas em implantação assistida</span>

            <span class="badge bg-primary">
                <?= (int)$totalImplantacoes ?> registro(s)
            </span>
        </div>

        <div class="card-body p-0">
            <?php if ($totalImplantacoes === 0): ?>
                <div class="empty-state">
                    Nenhuma implantação assistida cadastrada até o momento.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-os mb-0">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Responsável</th>
                                <th>Plano</th>
                                <th>Status</th>
                                <th>Teste</th>
                                <th>Contato</th>
                                <th width="120">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($implantacoes as $implantacao): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($implantacao["NomeFantasia"] ?? "-") ?></strong>

                                        <div class="os-subtitle">
                                            slug: <?= htmlspecialchars($implantacao["Slug"] ?? "-") ?>
                                        </div>

                                        <?php if (!empty($implantacao["Segmento"])): ?>
                                            <div class="os-subtitle">
                                                segmento: <?= htmlspecialchars($implantacao["Segmento"]) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong><?= htmlspecialchars($implantacao["ResponsavelNome"] ?? "-") ?></strong>

                                        <div class="os-subtitle">
                                            <?= htmlspecialchars($implantacao["ResponsavelEmail"] ?? "") ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($implantacao["PlanoNome"] ?? "Sem plano") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge <?= classeStatusImplantacao($implantacao) ?>">
                                            <?= htmlspecialchars(textoStatusImplantacao($implantacao)) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="small">
                                            Início:
                                            <strong><?= htmlspecialchars(formatarDataImplantacao($implantacao["DataInicioTeste"] ?? null)) ?></strong>
                                        </div>

                                        <div class="small">
                                            Fim:
                                            <strong><?= htmlspecialchars(formatarDataImplantacao($implantacao["DataFimTeste"] ?? null)) ?></strong>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="small">
                                            <?= htmlspecialchars($implantacao["Email"] ?? "-") ?>
                                        </div>

                                        <div class="small text-muted">
                                            <?= htmlspecialchars($implantacao["WhatsApp"] ?: ($implantacao["Telefone"] ?? "")) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <a
                                            href="empresa.php?id=<?= (int)$implantacao["EmpresaId"] ?>"
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
