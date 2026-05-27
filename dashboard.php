<?php
require_once "includes/proteger.php";
require_once "config/conexao.php";
require_once "includes/funcoes.php";
require_once "includes/planos.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioNome = $_SESSION["UsuarioNome"] ?? "Usuário";

function buscarValorEmpresa($conn, $sql, $empresaId)
{
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchColumn();
}

function classeStatusDashboard($status)
{
    if ($status === "Aberta") {
        return "bg-primary";
    }

    if ($status === "Em andamento") {
        return "bg-warning text-dark";
    }

    if ($status === "Aguardando cliente" || $status === "Aguardando peça") {
        return "bg-secondary";
    }

    if ($status === "Concluída") {
        return "bg-success";
    }

    if ($status === "Cancelada") {
        return "bg-danger";
    }

    return "bg-secondary";
}

function classePrioridadeDashboard($prioridade)
{
    if ($prioridade === "Baixa") {
        return "bg-info text-dark";
    }

    if ($prioridade === "Normal") {
        return "bg-secondary";
    }

    if ($prioridade === "Alta") {
        return "bg-warning text-dark";
    }

    if ($prioridade === "Urgente") {
        return "bg-danger";
    }

    return "bg-secondary";
}

$totalClientesAtivos = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_Clientes 
    WHERE Ativo = 1
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalServicosAtivos = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_Servicos 
    WHERE Ativo = 1
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalUsuariosAtivos = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_Usuarios 
    WHERE Ativo = 1
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalOSAbertas = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status = 'Aberta'
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalOSEmAndamento = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status = 'Em andamento'
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalOSAguardando = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status IN ('Aguardando cliente', 'Aguardando peça')
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalOSConcluidas = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status = 'Concluída'
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalOSCanceladas = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status = 'Cancelada'
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalOSAtrasadas = buscarValorEmpresa($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico
    WHERE DataPrevisao < CAST(GETDATE() AS DATE)
      AND Status NOT IN ('Concluída', 'Cancelada')
      AND EmpresaId = :EmpresaId
", $empresaId);

$valorPrevisto = buscarValorEmpresa($conn, "
    SELECT ISNULL(SUM(ValorPrevisto), 0)
    FROM OS_OrdensServico
    WHERE Status NOT IN ('Cancelada')
      AND EmpresaId = :EmpresaId
", $empresaId);

$valorFinalizado = buscarValorEmpresa($conn, "
    SELECT ISNULL(SUM(ValorFinal), 0)
    FROM OS_OrdensServico
    WHERE Status = 'Concluída'
      AND EmpresaId = :EmpresaId
", $empresaId);

$totalOSAtivas = $totalOSAbertas + $totalOSEmAndamento + $totalOSAguardando;

$planoAtual = obterPlanoEmpresa($conn, $empresaId);
$totalOSMes = totalOSMesEmpresa($conn, $empresaId);

$sqlUltimas = "
    SELECT TOP 6
        os.OrdemServicoId,
        os.CodigoOS,
        os.Titulo,
        os.Status,
        os.Prioridade,
        os.DataAbertura,
        os.DataPrevisao,
        c.Nome AS ClienteNome,
        s.Nome AS ServicoNome
    FROM OS_OrdensServico os
    INNER JOIN OS_Clientes c ON c.ClienteId = os.ClienteId
    LEFT JOIN OS_Servicos s ON s.ServicoId = os.ServicoId
    WHERE os.EmpresaId = :EmpresaId
    ORDER BY os.OrdemServicoId DESC
";

$stmtUltimas = $conn->prepare($sqlUltimas);
$stmtUltimas->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmtUltimas->execute();

$ultimasOrdens = $stmtUltimas->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "includes/header.php"; ?>
<?php require_once "includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">
                Olá, <?= htmlspecialchars($usuarioNome) ?> 👋
            </h3>

            <p>
                Veja o resumo da operação, acompanhe ordens de serviço e acesse ações rápidas.
            </p>
        </div>

        <a href="ordens/cadastrar.php" class="btn btn-primary">
            + Nova OS
        </a>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">OS Ativas</div>

                    <h3 class="mb-1 mt-2 text-primary">
                        <?= (int)$totalOSAtivas ?>
                    </h3>

                    <div class="input-help">
                        Abertas, em andamento ou aguardando.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Clientes Ativos</div>

                    <h3 class="mb-1 mt-2">
                        <?= (int)$totalClientesAtivos ?>
                    </h3>

                    <a href="clientes/listar.php" class="small text-decoration-none">
                        Ver clientes
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Valor Previsto</div>

                    <h3 class="mb-1 mt-2">
                        R$ <?= number_format((float)$valorPrevisto, 2, ",", ".") ?>
                    </h3>

                    <div class="input-help">
                        OS não canceladas.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Valor Finalizado</div>

                    <h3 class="mb-1 mt-2">
                        R$ <?= number_format((float)$valorFinalizado, 2, ",", ".") ?>
                    </h3>

                    <div class="input-help">
                        OS concluídas.
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Aberta" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="small text-muted">Abertas</div>
                        <h3 class="text-primary mb-0 mt-2"><?= (int)$totalOSAbertas ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Em%20andamento" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="small text-muted">Em andamento</div>
                        <h3 class="text-warning mb-0 mt-2"><?= (int)$totalOSEmAndamento ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Aguardando%20cliente" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="small text-muted">Aguardando</div>
                        <h3 class="text-secondary mb-0 mt-2"><?= (int)$totalOSAguardando ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Conclu%C3%ADda" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="small text-muted">Concluídas</div>
                        <h3 class="text-success mb-0 mt-2"><?= (int)$totalOSConcluidas ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Cancelada" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="small text-muted">Canceladas</div>
                        <h3 class="text-danger mb-0 mt-2"><?= (int)$totalOSCanceladas ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php" class="text-decoration-none">
                <div class="card shadow-sm h-100 border border-danger">
                    <div class="card-body text-center">
                        <div class="small text-muted">Atrasadas</div>
                        <h3 class="text-danger mb-0 mt-2"><?= (int)$totalOSAtrasadas ?></h3>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg-8">
            <div class="card form-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Últimas Ordens de Serviço</span>

                    <a href="ordens/listar.php" class="btn btn-sm btn-outline-primary">
                        Ver todas
                    </a>
                </div>

                <div class="card-body p-0">

                    <?php if (count($ultimasOrdens) === 0): ?>
                        <div class="empty-state">
                            Nenhuma ordem de serviço cadastrada.
                        </div>
                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-os mb-0">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Serviço</th>
                                        <th>Status</th>
                                        <th>Prioridade</th>
                                        <th>Previsão</th>
                                        <th width="90">Ação</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($ultimasOrdens as $ordem): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(formatarCodigoOS($ordem["OrdemServicoId"], $ordem["CodigoOS"] ?? null, $ordem["DataAbertura"] ?? null)) ?>
                                                </strong>
                                                <div class="os-subtitle">
                                                    <?= htmlspecialchars($ordem["Titulo"] ?? "") ?>
                                                </div>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($ordem["ClienteNome"] ?? "") ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?>
                                            </td>

                                            <td>
                                                <span class="badge <?= classeStatusDashboard($ordem["Status"] ?? "") ?>">
                                                    <?= htmlspecialchars($ordem["Status"] ?? "-") ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="badge <?= classePrioridadeDashboard($ordem["Prioridade"] ?? "") ?>">
                                                    <?= htmlspecialchars($ordem["Prioridade"] ?? "-") ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= !empty($ordem["DataPrevisao"])
                                                    ? date("d/m/Y", strtotime($ordem["DataPrevisao"]))
                                                    : "-"
                                                ?>
                                            </td>

                                            <td>
                                                <a 
                                                    href="ordens/visualizar.php?id=<?= (int)$ordem["OrdemServicoId"] ?>" 
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

        <div class="col-lg-4">

            <div class="card form-card mb-3">
                <div class="card-header">
                    Ações rápidas
                </div>

                <div class="card-body">

                    <div class="d-grid gap-2">
                        <a href="ordens/cadastrar.php" class="btn btn-primary">
                            + Criar nova OS
                        </a>

                        <a href="clientes/cadastrar.php" class="btn btn-outline-primary">
                            Cadastrar cliente
                        </a>

                        <a href="servicos/listar.php" class="btn btn-outline-secondary">
                            Gerenciar serviços
                        </a>

                        <a href="empresa/editar.php" class="btn btn-outline-secondary">
                            Minha empresa
                        </a>
                    </div>

                </div>
            </div>

            <div class="card form-card">
                <div class="card-header">
                    Plano atual
                </div>

                <div class="card-body">

                    <?php if ($planoAtual): ?>
                        <div class="small text-muted">Plano</div>

                        <h4 class="text-primary mb-2">
                            <?= htmlspecialchars($planoAtual["Nome"]) ?>
                        </h4>

                        <div class="mb-3">
                            <strong>OS no mês:</strong>
                            <?= (int)$totalOSMes ?>

                            <?php if ($planoAtual["LimiteOSMes"] === null || $planoAtual["LimiteOSMes"] === ""): ?>
                                / Ilimitado
                            <?php else: ?>
                                / <?= (int)$planoAtual["LimiteOSMes"] ?>
                            <?php endif; ?>
                        </div>

                        <a href="planos/meu_plano.php" class="btn btn-sm btn-outline-primary">
                            Ver plano
                        </a>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            Nenhum plano ativo encontrado.
                        </p>
                    <?php endif; ?>

                </div>
            </div>

        </div>

    </div>

    <div class="row g-3">

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Serviços ativos</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalServicosAtivos ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Usuários ativos</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalUsuariosAtivos ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Clientes ativos</div>

                    <h3 class="mb-0 mt-2">
                        <?= (int)$totalClientesAtivos ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once "includes/footer.php"; ?>