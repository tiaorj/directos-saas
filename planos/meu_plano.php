<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/planos.php";

$empresaId = (int)$_SESSION["EmpresaId"];

$planoAtual = obterPlanoEmpresa($conn, $empresaId);
$totalMes = totalOSMesEmpresa($conn, $empresaId);

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
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<style>
    body {
        background: #f5f6f8;
    }

    .plan-hero {
        background: linear-gradient(135deg, #212529, #0d6efd);
        color: #fff;
        border-radius: 18px;
        padding: 28px;
        margin-bottom: 24px;
    }

    .plan-card {
        border: none;
        border-radius: 16px;
        height: 100%;
    }

    .plan-card-current {
        border: 2px solid #0d6efd;
    }

    .price {
        font-size: 2rem;
        font-weight: 700;
    }

    .usage-box {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
    }
</style>

<div class="container">

    <div class="plan-hero shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-2">
                    Meu Plano
                </h3>

                <p class="mb-0">
                    Acompanhe seu plano atual, limite mensal e recursos disponíveis.
                </p>
            </div>

            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="../dashboard.php" class="btn btn-light">
                    Voltar ao Dashboard
                </a>
            </div>
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

    <?php if ($planoAtual): ?>
        <div class="usage-box shadow-sm mb-4">
            <div class="row align-items-center">

                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="mb-1">Plano atual</h5>
                    <h3 class="text-primary mb-0">
                        <?= htmlspecialchars($planoAtual["Nome"]) ?>
                    </h3>
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="mb-1">Uso de OS no mês</h5>

                    <?php if ($planoAtual["LimiteOSMes"] === null || $planoAtual["LimiteOSMes"] === ""): ?>
                        <h3 class="mb-0">
                            <?= (int)$totalMes ?> / Ilimitado
                        </h3>
                    <?php else: ?>
                        <h3 class="mb-0">
                            <?= (int)$totalMes ?> / <?= (int)$planoAtual["LimiteOSMes"] ?>
                        </h3>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <h5 class="mb-1">Valor mensal</h5>
                    <h3 class="mb-0">
                        R$ <?= number_format((float)$planoAtual["ValorMensal"], 2, ",", ".") ?>
                    </h3>
                </div>

            </div>

            <?php if ($planoAtual["LimiteOSMes"] !== null && $planoAtual["LimiteOSMes"] !== ""): ?>
                <?php
                    $limite = (int)$planoAtual["LimiteOSMes"];
                    $percentual = $limite > 0 ? min(100, round(($totalMes / $limite) * 100)) : 0;
                ?>

                <div class="mt-4">
                    <div class="d-flex justify-content-between">
                        <small>Utilização mensal</small>
                        <small><?= $percentual ?>%</small>
                    </div>

                    <div class="progress" style="height: 12px;">
                        <div 
                            class="progress-bar <?= $percentual >= 100 ? "bg-danger" : "bg-primary" ?>" 
                            role="progressbar" 
                            style="width: <?= $percentual ?>%;">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            Nenhum plano ativo encontrado para esta empresa.
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <?php foreach ($planos as $plano): ?>
            <?php
                $ehAtual = $planoAtual && (int)$planoAtual["PlanoId"] === (int)$plano["PlanoId"];
            ?>

            <div class="col-md-4">
                <div class="card plan-card shadow-sm <?= $ehAtual ? "plan-card-current" : "" ?>">
                    <div class="card-body">

                        <?php if ($ehAtual): ?>
                            <span class="badge bg-primary mb-2">
                                Plano atual
                            </span>
                        <?php endif; ?>

                        <h4><?= htmlspecialchars($plano["Nome"]) ?></h4>

                        <p class="text-muted">
                            <?= htmlspecialchars($plano["Descricao"] ?? "") ?>
                        </p>

                        <div class="price mb-3">
                            R$ <?= number_format((float)$plano["ValorMensal"], 2, ",", ".") ?>
                            <small class="text-muted fs-6">/mês</small>
                        </div>

                        <ul class="mb-4">
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
        <?php endforeach; ?>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>