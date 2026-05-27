<?php
require_once "includes/proteger.php";
require_once "config/conexao.php";
require_once "includes/funcoes.php";

$empresaId = (int)$_SESSION["EmpresaId"];
$usuarioNome = $_SESSION["UsuarioNome"] ?? "Usuário";

function buscarValorEmpresa($conn, $sql, $empresaId)
{
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchColumn();
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

$sqlUltimas = "
    SELECT TOP 5
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

<style>
    body {
        background: #f5f6f8;
    }

    .dash-hero {
        background: linear-gradient(135deg, #212529, #0d6efd);
        color: #fff;
        border-radius: 18px;
        padding: 28px;
        margin-bottom: 24px;
    }

    .dash-hero h3 {
        font-weight: 700;
    }

    .metric-card {
        border: none;
        border-radius: 16px;
        height: 100%;
    }

    .metric-label {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 4px;
    }

    .metric-value {
        font-size: 1.9rem;
        font-weight: 700;
        margin-bottom: 0;
    }

    .status-card {
        border: none;
        border-radius: 14px;
    }

    .quick-action-card {
        border: none;
        border-radius: 16px;
    }
</style>

<div class="container">

    <div class="dash-hero shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-2">
                    Olá, <?= htmlspecialchars($usuarioNome) ?> 👋
                </h3>

                <p class="mb-0">
                    Aqui está o resumo das suas ordens de serviço, clientes e valores da operação.
                </p>
            </div>

            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="ordens/cadastrar.php" class="btn btn-light">
                    Nova Ordem de Serviço
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card metric-card shadow-sm">
                <div class="card-body">
                    <div class="metric-label">OS Ativas</div>
                    <p class="metric-value text-primary"><?= (int)$totalOSAtivas ?></p>
                    <small class="text-muted">
                        Abertas, em andamento ou aguardando
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card shadow-sm">
                <div class="card-body">
                    <div class="metric-label">Clientes Ativos</div>
                    <p class="metric-value"><?= (int)$totalClientesAtivos ?></p>
                    <a href="clientes/listar.php" class="small text-decoration-none">
                        Ver clientes
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card shadow-sm">
                <div class="card-body">
                    <div class="metric-label">Valor Previsto</div>
                    <p class="metric-value">
                        R$ <?= number_format((float)$valorPrevisto, 2, ",", ".") ?>
                    </p>
                    <small class="text-muted">
                        OS não canceladas
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card shadow-sm">
                <div class="card-body">
                    <div class="metric-label">Valor Finalizado</div>
                    <p class="metric-value">
                        R$ <?= number_format((float)$valorFinalizado, 2, ",", ".") ?>
                    </p>
                    <small class="text-muted">
                        OS concluídas
                    </small>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Aberta" class="text-decoration-none">
                <div class="card status-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted small">Abertas</div>
                        <h3 class="text-primary mb-0"><?= (int)$totalOSAbertas ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Em%20andamento" class="text-decoration-none">
                <div class="card status-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted small">Em andamento</div>
                        <h3 class="text-warning mb-0"><?= (int)$totalOSEmAndamento ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Aguardando%20cliente" class="text-decoration-none">
                <div class="card status-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted small">Aguardando</div>
                        <h3 class="text-secondary mb-0"><?= (int)$totalOSAguardando ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Conclu%C3%ADda" class="text-decoration-none">
                <div class="card status-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted small">Concluídas</div>
                        <h3 class="text-success mb-0"><?= (int)$totalOSConcluidas ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php?status=Cancelada" class="text-decoration-none">
                <div class="card status-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted small">Canceladas</div>
                        <h3 class="text-danger mb-0"><?= (int)$totalOSCanceladas ?></h3>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-2">
            <a href="ordens/listar.php" class="text-decoration-none">
                <div class="card status-card shadow-sm border-danger">
                    <div class="card-body text-center">
                        <div class="text-muted small">Atrasadas</div>
                        <h3 class="text-danger mb-0"><?= (int)$totalOSAtrasadas ?></h3>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <h6>Nova OS</h6>
                    <p class="text-muted small">
                        Abra uma nova ordem de serviço para um cliente.
                    </p>
                    <a href="ordens/cadastrar.php" class="btn btn-sm btn-primary">
                        Criar OS
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <h6>Novo Cliente</h6>
                    <p class="text-muted small">
                        Cadastre rapidamente um novo cliente.
                    </p>
                    <a href="clientes/cadastrar.php" class="btn btn-sm btn-outline-primary">
                        Cadastrar Cliente
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <h6>Minha Empresa</h6>
                    <p class="text-muted small">
                        Atualize nome, WhatsApp e dados da empresa.
                    </p>
                    <a href="empresa/editar.php" class="btn btn-sm btn-outline-dark">
                        Configurar
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <h6>Serviços</h6>
                    <p class="text-muted small">
                        Gerencie os serviços oferecidos pela empresa.
                    </p>
                    <a href="servicos/listar.php" class="btn btn-sm btn-outline-dark">
                        Ver Serviços
                    </a>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span>Últimas Ordens de Serviço</span>

            <a href="ordens/listar.php" class="btn btn-sm btn-light">
                Ver todas
            </a>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Prioridade</th>
                            <th>Previsão</th>
                            <th width="100">Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($ultimasOrdens) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    Nenhuma ordem de serviço cadastrada.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($ultimasOrdens as $ordem): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(formatarCodigoOS($ordem["OrdemServicoId"], $ordem["CodigoOS"] ?? null, $ordem["DataAbertura"] ?? null)) ?>
                                </td>

                                <td><?= htmlspecialchars($ordem["ClienteNome"] ?? "") ?></td>

                                <td><?= htmlspecialchars($ordem["ServicoNome"] ?? "Não informado") ?></td>

                                <td><?= htmlspecialchars($ordem["Titulo"] ?? "") ?></td>

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

                                    <span class="badge <?= $classeStatus ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                        $prioridade = $ordem["Prioridade"];
                                        $classePrioridade = "bg-secondary";

                                        if ($prioridade === "Baixa") {
                                            $classePrioridade = "bg-info text-dark";
                                        } elseif ($prioridade === "Alta") {
                                            $classePrioridade = "bg-warning text-dark";
                                        } elseif ($prioridade === "Urgente") {
                                            $classePrioridade = "bg-danger";
                                        }
                                    ?>

                                    <span class="badge <?= $classePrioridade ?>">
                                        <?= htmlspecialchars($prioridade) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= !empty($ordem["DataPrevisao"])
                                        ? date("d/m/Y", strtotime($ordem["DataPrevisao"]))
                                        : "-"
                                    ?>
                                </td>

                                <td>
                                    <a href="ordens/visualizar.php?id=<?= $ordem["OrdemServicoId"] ?>" 
                                       class="btn btn-sm btn-info">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<?php require_once "includes/footer.php"; ?>