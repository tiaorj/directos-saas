<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/planos.php";

$empresaId = (int)$_SESSION["EmpresaId"];

$planoAtual = obterPlanoEmpresa($conn, $empresaId);
$totalMes = totalOSMesEmpresa($conn, $empresaId);
$totalUsuarios = totalUsuariosEmpresa($conn, $empresaId);

$sqlPlanos = "
    SELECT
        PlanoId,
        Nome,
        Slug,
        Descricao,
        LimiteOSMes,
        LimiteUsuarios,
        PermiteAnexos,
        PermiteAreaCliente,
        PermiteWhatsapp,
        ValorMensal
    FROM OS_Planos
    WHERE Ativo = 1
    ORDER BY ValorMensal
";

$stmtPlanos = $conn->prepare($sqlPlanos);
$stmtPlanos->execute();

$planos = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);

$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";

function formatarLimitePlano($valor, $textoIlimitado = "Ilimitado")
{
    if ($valor === null || $valor === "") {
        return $textoIlimitado;
    }

    return (string)(int)$valor;
}

function percentualUsoPlano($total, $limite)
{
    if ($limite === null || $limite === "" || (int)$limite <= 0) {
        return 0;
    }

    return min(100, round(((int)$total / (int)$limite) * 100));
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Meu Plano</h3>
            <p>
                Acompanhe sua assinatura, limites mensais e recursos disponíveis no DirectOS.
            </p>
        </div>

        <a href="../dashboard.php" class="btn btn-outline-secondary">
            Voltar
        </a>
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

    <?php if ($planoAtual): ?>
        <?php
            $limiteOS = $planoAtual["LimiteOSMes"];
            $limiteUsuarios = $planoAtual["LimiteUsuarios"];

            $percentualOS = percentualUsoPlano($totalMes, $limiteOS);
            $percentualUsuarios = percentualUsoPlano($totalUsuarios, $limiteUsuarios);
        ?>

        <div class="row g-3 mb-4">

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-muted">Plano atual</div>

                        <h4 class="mb-1 mt-2 text-primary">
                            <?= htmlspecialchars($planoAtual["Nome"]) ?>
                        </h4>

                        <span class="badge bg-success">
                            <?= htmlspecialchars($planoAtual["StatusAssinatura"] ?? "Ativa") ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-muted">Valor mensal</div>

                        <h4 class="mb-0 mt-2">
                            R$ <?= number_format((float)$planoAtual["ValorMensal"], 2, ",", ".") ?>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-muted">OS no mês</div>

                        <h4 class="mb-0 mt-2">
                            <?= (int)$totalMes ?> / <?= htmlspecialchars(formatarLimitePlano($limiteOS)) ?>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-muted">Usuários ativos</div>

                        <h4 class="mb-0 mt-2">
                            <?= (int)$totalUsuarios ?> / <?= htmlspecialchars(formatarLimitePlano($limiteUsuarios)) ?>
                        </h4>
                    </div>
                </div>
            </div>

        </div>

        <div class="card form-card mb-4">
            <div class="card-header">
                Uso do Plano
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-4 mb-md-0">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Ordens de Serviço no mês</strong>

                            <?php if ($limiteOS === null || $limiteOS === ""): ?>
                                <span class="text-muted">
                                    <?= (int)$totalMes ?> / Ilimitado
                                </span>
                            <?php else: ?>
                                <span class="text-muted">
                                    <?= (int)$totalMes ?> / <?= (int)$limiteOS ?> · <?= $percentualOS ?>%
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($limiteOS === null || $limiteOS === ""): ?>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-success" style="width: 100%;"></div>
                            </div>
                        <?php else: ?>
                            <div class="progress" style="height: 12px;">
                                <div 
                                    class="progress-bar <?= $percentualOS >= 100 ? "bg-danger" : "bg-primary" ?>" 
                                    style="width: <?= $percentualOS ?>%;">
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($limiteOS !== null && $limiteOS !== "" && $percentualOS >= 80): ?>
                            <div class="input-help mt-2">
                                Você está próximo do limite mensal de OS deste plano.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Usuários ativos</strong>

                            <?php if ($limiteUsuarios === null || $limiteUsuarios === ""): ?>
                                <span class="text-muted">
                                    <?= (int)$totalUsuarios ?> / Ilimitado
                                </span>
                            <?php else: ?>
                                <span class="text-muted">
                                    <?= (int)$totalUsuarios ?> / <?= (int)$limiteUsuarios ?> · <?= $percentualUsuarios ?>%
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($limiteUsuarios === null || $limiteUsuarios === ""): ?>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-success" style="width: 100%;"></div>
                            </div>
                        <?php else: ?>
                            <div class="progress" style="height: 12px;">
                                <div 
                                    class="progress-bar <?= $percentualUsuarios >= 100 ? "bg-danger" : "bg-primary" ?>" 
                                    style="width: <?= $percentualUsuarios ?>%;">
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($limiteUsuarios !== null && $limiteUsuarios !== "" && $percentualUsuarios >= 80): ?>
                            <div class="input-help mt-2">
                                Você está próximo do limite de usuários deste plano.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-warning">
            Nenhum plano ativo encontrado para esta empresa.
        </div>
    <?php endif; ?>

    <div class="form-header mb-3">
        <div>
            <h4 class="mb-1">Planos disponíveis</h4>
            <p>Compare os recursos e altere o plano da empresa quando necessário.</p>
        </div>
    </div>

    <div class="row g-4">

        <?php foreach ($planos as $plano): ?>
            <?php
                $ehAtual = $planoAtual && (int)$planoAtual["PlanoId"] === (int)$plano["PlanoId"];
                $valorMensal = (float)$plano["ValorMensal"];
            ?>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100 <?= $ehAtual ? "border border-primary border-2" : "" ?>">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h4 class="mb-1">
                                    <?= htmlspecialchars($plano["Nome"]) ?>
                                </h4>

                                <?php if ($ehAtual): ?>
                                    <span class="badge bg-primary">
                                        Plano atual
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="text-muted">
                            <?= htmlspecialchars($plano["Descricao"] ?? "") ?>
                        </p>

                        <div class="mb-3">
                            <span style="font-size: 2rem; font-weight: 800;">
                                R$ <?= number_format($valorMensal, 2, ",", ".") ?>
                            </span>

                            <span class="text-muted">
                                /mês
                            </span>
                        </div>

                        <ul class="mb-4 ps-3">
                            <?php if ($plano["LimiteOSMes"] === null || $plano["LimiteOSMes"] === ""): ?>
                                <li>OS ilimitadas por mês</li>
                            <?php else: ?>
                                <li>Até <?= (int)$plano["LimiteOSMes"] ?> OS por mês</li>
                            <?php endif; ?>

                            <?php if ($plano["LimiteUsuarios"] === null || $plano["LimiteUsuarios"] === ""): ?>
                                <li>Usuários ilimitados</li>
                            <?php else: ?>
                                <li>Até <?= (int)$plano["LimiteUsuarios"] ?> usuário(s)</li>
                            <?php endif; ?>

                            <?php if ((int)$plano["PermiteAnexos"] === 1): ?>
                                <li>Anexos e fotos</li>
                            <?php endif; ?>

                            <?php if ((int)$plano["PermiteAreaCliente"] === 1): ?>
                                <li>Área do cliente por link</li>
                            <?php endif; ?>

                            <?php if ((int)$plano["PermiteWhatsapp"] === 1): ?>
                                <li>Envio por WhatsApp</li>
                            <?php endif; ?>
                        </ul>

                        <div class="mt-auto">
                            <?php if ($ehAtual): ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    Plano atual
                                </button>
                            <?php else: ?>
                                <a 
                                    href="alterar.php?plano=<?= (int)$plano["PlanoId"] ?>" 
                                    class="btn btn-primary w-100"
                                    onclick="return confirm('Deseja alterar para o plano <?= htmlspecialchars($plano["Nome"]) ?>?')"
                                >
                                    Alterar para este plano
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>