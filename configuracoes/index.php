<?php
require_once "../includes/proteger.php";
require_once "../includes/permissoes.php";
require_once "../config/conexao.php";
require_once "../includes/csrf.php";

exigirPerfil(["Admin"]);

$empresaId = (int)$_SESSION["EmpresaId"];

$sqlEmpresa = "
    SELECT
        EmpresaId,
        NomeFantasia,
        Email,
        WhatsApp,
        OcultarOnboarding
    FROM OS_Empresas
    WHERE EmpresaId = :EmpresaId
";

$stmtEmpresa = $conn->prepare($sqlEmpresa);
$stmtEmpresa->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtEmpresa->execute();

$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    die("Empresa não encontrada.");
}

function contarRegistrosConfig($conn, $sql, $empresaId)
{
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

$totalServicosAtivos = contarRegistrosConfig($conn, "
    SELECT COUNT(*)
    FROM OS_Servicos
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
", $empresaId);

$totalClientesAtivos = contarRegistrosConfig($conn, "
    SELECT COUNT(*)
    FROM OS_Clientes
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
", $empresaId);

$totalOrdensServico = contarRegistrosConfig($conn, "
    SELECT COUNT(*)
    FROM OS_OrdensServico
    WHERE EmpresaId = :EmpresaId
", $empresaId);

$empresaCompleta = !empty($empresa["NomeFantasia"]) 
    && (!empty($empresa["Email"]) || !empty($empresa["WhatsApp"]));

$onboardingConcluido = $empresaCompleta 
    && $totalServicosAtivos > 0 
    && $totalClientesAtivos > 0 
    && $totalOrdensServico > 0;

$onboardingOculto = (int)($empresa["OcultarOnboarding"] ?? 0) === 1;

$itensOnboarding = [
    [
        "titulo" => "Dados da empresa",
        "concluido" => $empresaCompleta
    ],
    [
        "titulo" => "Serviço cadastrado",
        "concluido" => $totalServicosAtivos > 0
    ],
    [
        "titulo" => "Cliente cadastrado",
        "concluido" => $totalClientesAtivos > 0
    ],
    [
        "titulo" => "Primeira OS criada",
        "concluido" => $totalOrdensServico > 0
    ]
];

$totalConcluidos = 0;

foreach ($itensOnboarding as $item) {
    if ($item["concluido"]) {
        $totalConcluidos++;
    }
}

$percentualOnboarding = count($itensOnboarding) > 0
    ? round(($totalConcluidos / count($itensOnboarding)) * 100)
    : 0;
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Configurações</h3>
            <p>
                Gerencie preferências gerais do DirectOS para sua empresa.
            </p>
        </div>

        <a href="../dashboard.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Empresa</div>

                    <h5 class="mb-1 mt-2">
                        <?= htmlspecialchars($empresa["NomeFantasia"] ?? "-") ?>
                    </h5>

                    <a href="../empresa/editar.php" class="small text-decoration-none">
                        Editar dados da empresa
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Checklist inicial</div>

                    <?php if ($onboardingConcluido): ?>
                        <h5 class="text-success mb-1 mt-2">
                            Concluído
                        </h5>
                    <?php elseif ($onboardingOculto): ?>
                        <h5 class="text-secondary mb-1 mt-2">
                            Oculto
                        </h5>
                    <?php else: ?>
                        <h5 class="text-primary mb-1 mt-2">
                            Ativo
                        </h5>
                    <?php endif; ?>

                    <span class="small text-muted">
                        <?= (int)$percentualOnboarding ?>% concluído
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Plano e assinatura</div>

                    <h5 class="mb-1 mt-2">
                        Meu Plano
                    </h5>

                    <a href="../planos/meu_plano.php" class="small text-decoration-none">
                        Ver plano atual
                    </a>
                </div>
            </div>
        </div>

    </div>

    <div class="card form-card mb-4">
        <div class="card-header">
            Onboarding inicial
        </div>

        <div class="card-body">

            <div class="row align-items-center">

                <div class="col-lg-8">
                    <h5 class="mb-2">
                        Checklist de primeiros passos
                    </h5>

                    <p class="text-muted">
                        Esse checklist ajuda novas empresas a configurar o DirectOS: completar dados da empresa, cadastrar serviço, cadastrar cliente e criar a primeira OS.
                    </p>

                    <div class="progress mb-3" style="height: 12px;">
                        <div 
                            class="progress-bar <?= $onboardingConcluido ? "bg-success" : "bg-primary" ?>" 
                            style="width: <?= (int)$percentualOnboarding ?>%;">
                        </div>
                    </div>

                    <div class="row g-2">

                        <?php foreach ($itensOnboarding as $item): ?>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>
                                            <?= htmlspecialchars($item["titulo"]) ?>
                                        </strong>

                                        <?php if ($item["concluido"]): ?>
                                            <span class="badge bg-success">
                                                OK
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                Pendente
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>

                <div class="col-lg-4 mt-4 mt-lg-0">

                    <?php if ($onboardingConcluido): ?>

                        <div class="alert alert-success mb-3">
                            <strong>Checklist concluído.</strong>
                            <br>
                            Todos os primeiros passos foram completados.
                        </div>

                        <a href="../dashboard.php" class="btn btn-success w-100">
                            Ir para o Dashboard
                        </a>

                    <?php elseif ($onboardingOculto): ?>

                        <div class="alert alert-info mb-3">
                            <strong>Checklist oculto.</strong>
                            <br>
                            Ele não aparece no Dashboard, mesmo existindo pendências.
                        </div>

                        <a 
                            href="../empresa/alternar_onboarding.php?acao=exibir&origem=configuracoes&<?= csrfTokenUrl() ?>"
                            class="btn btn-primary w-100"
                        >
                            Reexibir checklist
                        </a>

                    <?php else: ?>

                        <div class="alert alert-light border mb-3">
                            <strong>Checklist ativo.</strong>
                            <br>
                            Ele aparece no Dashboard enquanto houver pendências.
                        </div>

                        <a 
                            href="../empresa/alternar_onboarding.php?acao=ocultar&origem=configuracoes&<?= csrfTokenUrl() ?>"
                            class="btn btn-outline-secondary w-100"
                            onclick="return confirm('Deseja ocultar o checklist inicial do Dashboard?')"
                        >
                            Ocultar checklist
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>
    </div>

    <div class="card form-card">
        <div class="card-header">
            Atalhos de configuração
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6>Minha Empresa</h6>

                            <p class="text-muted small">
                                Atualize nome fantasia, WhatsApp, e-mail e slug da empresa.
                            </p>

                            <a href="../empresa/editar.php" class="btn btn-sm btn-outline-primary">
                                Abrir
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6>Meu Plano</h6>

                            <p class="text-muted small">
                                Veja limites, uso mensal e altere o plano da empresa.
                            </p>

                            <a href="../planos/meu_plano.php" class="btn btn-sm btn-outline-primary">
                                Abrir
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6>Usuários</h6>

                            <p class="text-muted small">
                                Gerencie usuários da empresa conforme o limite do plano.
                            </p>

                            <a href="../usuarios/listar.php" class="btn btn-sm btn-outline-primary">
                                Abrir
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
