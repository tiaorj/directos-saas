<?php
require_once "includes/proteger.php";
require_once "config/conexao.php";
require_once "includes/funcoes.php";

function buscarValor($conn, $sql)
{
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

$totalClientesAtivos = buscarValor($conn, "
    SELECT COUNT(*) 
    FROM OS_Clientes 
    WHERE Ativo = 1
");

$totalServicosAtivos = buscarValor($conn, "
    SELECT COUNT(*) 
    FROM OS_Servicos 
    WHERE Ativo = 1
");

$totalOSAbertas = buscarValor($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status = 'Aberta'
");

$totalOSEmAndamento = buscarValor($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status = 'Em andamento'
");

$totalOSAguardando = buscarValor($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status IN ('Aguardando cliente', 'Aguardando peça')
");

$totalOSConcluidas = buscarValor($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status = 'Concluída'
");

$totalOSCanceladas = buscarValor($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico 
    WHERE Status = 'Cancelada'
");

$totalOSAtrasadas = buscarValor($conn, "
    SELECT COUNT(*) 
    FROM OS_OrdensServico
    WHERE DataPrevisao < CAST(GETDATE() AS DATE)
      AND Status NOT IN ('Concluída', 'Cancelada')
");

$valorPrevisto = buscarValor($conn, "
    SELECT ISNULL(SUM(ValorPrevisto), 0)
    FROM OS_OrdensServico
    WHERE Status NOT IN ('Cancelada')
");

$valorFinalizado = buscarValor($conn, "
    SELECT ISNULL(SUM(ValorFinal), 0)
    FROM OS_OrdensServico
    WHERE Status = 'Concluída'
");

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
    ORDER BY os.OrdemServicoId DESC
";

$stmtUltimas = $conn->prepare($sqlUltimas);
$stmtUltimas->execute();
$ultimasOrdens = $stmtUltimas->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once "includes/header.php"; ?>
<?php require_once "includes/menu.php"; ?>

<div class="container">

    <div class="mb-4">
        <h3>Dashboard</h3>
        <p class="text-muted mb-0">
            Visão geral do sistema de Ordens de Serviço
        </p>
    </div>

    <div class="row mb-4">

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h6 class="text-muted">Clientes Ativos</h6>
                    <h3><?= $totalClientesAtivos ?></h3>
                    <a href="clientes/listar.php" class="small">Ver clientes</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-info">
                <div class="card-body">
                    <h6 class="text-muted">Serviços Ativos</h6>
                    <h3><?= $totalServicosAtivos ?></h3>
                    <a href="servicos/listar.php" class="small">Ver serviços</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <h6 class="text-muted">Valor Previsto</h6>
                    <h3>R$ <?= number_format((float)$valorPrevisto, 2, ",", ".") ?></h3>
                    <span class="small text-muted">OS não canceladas</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-dark">
                <div class="card-body">
                    <h6 class="text-muted">Valor Finalizado</h6>
                    <h3>R$ <?= number_format((float)$valorFinalizado, 2, ",", ".") ?></h3>
                    <span class="small text-muted">OS concluídas</span>
                </div>
            </div>
        </div>

    </div>

    <div class="row mb-4">

        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6>Abertas</h6>
                    <h3 class="text-primary"><?= $totalOSAbertas ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6>Em Andamento</h6>
                    <h3 class="text-warning"><?= $totalOSEmAndamento ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6>Aguardando</h6>
                    <h3 class="text-secondary"><?= $totalOSAguardando ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6>Concluídas</h6>
                    <h3 class="text-success"><?= $totalOSConcluidas ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6>Canceladas</h6>
                    <h3 class="text-danger"><?= $totalOSCanceladas ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-2 mb-3">
            <div class="card shadow-sm border-danger">
                <div class="card-body text-center">
                    <h6>Atrasadas</h6>
                    <h3 class="text-danger"><?= $totalOSAtrasadas ?></h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm">
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