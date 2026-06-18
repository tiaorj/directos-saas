<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";
require_once "../includes/planos.php";
require_once "../includes/csrf.php";
require_once "../includes/permissoes.php";

$empresaId = (int)$_SESSION["EmpresaId"];

$usuarioSuperAdmin = usuarioEhSuperAdmin();

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
      AND Slug IN ('starter', 'profissional', 'empresa')
    ORDER BY
        CASE Slug
            WHEN 'starter' THEN 1
            WHEN 'profissional' THEN 2
            WHEN 'empresa' THEN 3
            ELSE 99
        END
";

$stmtPlanos = $conn->prepare($sqlPlanos);
$stmtPlanos->execute();

$planosBanco = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);

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

function montarPlanoComercial($planoBanco)
{
    $modelos = [
        "starter" => [
            "NomeComercial" => "Starter",
            "DescricaoComercial" => "Para prestadores individuais ou pequenos negócios começando a organizar ordens de serviço.",
            "ValorComercial" => 39.00,
            "LimiteOSMesComercial" => 30,
            "LimiteUsuariosComercial" => 1,
            "Destaque" => false,
            "Tag" => "Entrada",
            "Recursos" => [
                "Até 30 OS por mês",
                "1 usuário",
                "Cadastro de clientes",
                "Cadastro de serviços",
                "Controle básico de OS",
                "Status e prioridades",
                "Financeiro básico",
                "Recibo da OS"
            ]
        ],
        "profissional" => [
            "NomeComercial" => "Profissional",
            "DescricaoComercial" => "Para pequenas empresas que já possuem rotina de atendimento e precisam de mais organização.",
            "ValorComercial" => 79.00,
            "LimiteOSMesComercial" => 150,
            "LimiteUsuariosComercial" => 3,
            "Destaque" => true,
            "Tag" => "Mais indicado",
            "Recursos" => [
                "Até 150 OS por mês",
                "Até 3 usuários",
                "Assistente IA",
                "Checklist padrão por serviço",
                "Campos personalizados",
                "Área do cliente por link",
                "Recebimentos parciais",
                "Recibo geral e por pagamento",
                "WhatsApp assistido",
                "Relatórios operacionais",
                "Relatório financeiro",
                "Exportação CSV"
            ]
        ],
        "empresa" => [
            "NomeComercial" => "Empresa",
            "DescricaoComercial" => "Para empresas com maior volume de OS ou mais usuários no atendimento.",
            "ValorComercial" => 149.00,
            "LimiteOSMesComercial" => null,
            "LimiteUsuariosComercial" => 10,
            "Destaque" => false,
            "Tag" => "Operação completa",
            "Recursos" => [
                "OS ilimitadas",
                "Até 10 usuários",
                "Todos os recursos do Profissional",
                "Segmento da empresa",
                "Modelos prontos por segmento",
                "Relatórios completos",
                "Suporte inicial para implantação",
                "Evolução futura para automação com n8n"
            ]
        ]
    ];

    $slug = $planoBanco["Slug"] ?? "";

    if (!isset($modelos[$slug])) {
        return null;
    }

    $modelo = $modelos[$slug];

    $planoBanco["NomeExibicao"] = $modelo["NomeComercial"];
    $planoBanco["DescricaoExibicao"] = $modelo["DescricaoComercial"];
    $planoBanco["ValorExibicao"] = $modelo["ValorComercial"];
    $planoBanco["LimiteOSMesExibicao"] = $modelo["LimiteOSMesComercial"];
    $planoBanco["LimiteUsuariosExibicao"] = $modelo["LimiteUsuariosComercial"];
    $planoBanco["RecursosExibicao"] = $modelo["Recursos"];
    $planoBanco["Destaque"] = $modelo["Destaque"];
    $planoBanco["Tag"] = $modelo["Tag"];

    return $planoBanco;
}

$planos = [];

foreach ($planosBanco as $planoBanco) {
    $planoComercial = montarPlanoComercial($planoBanco);

    if ($planoComercial) {
        $planos[] = $planoComercial;
    }
}

$planoAtualComercial = null;

if ($planoAtual) {
    foreach ($planos as $plano) {
        if ((int)$plano["PlanoId"] === (int)$planoAtual["PlanoId"]) {
            $planoAtualComercial = $plano;
            break;
        }
    }

    if (!$planoAtualComercial) {
        $planoAtualComercial = $planoAtual;
        $planoAtualComercial["NomeExibicao"] = $planoAtual["Nome"] ?? "Plano atual";
        $planoAtualComercial["DescricaoExibicao"] = $planoAtual["Descricao"] ?? "";
        $planoAtualComercial["ValorExibicao"] = (float)($planoAtual["ValorMensal"] ?? 0);
        $planoAtualComercial["LimiteOSMesExibicao"] = $planoAtual["LimiteOSMes"] ?? null;
        $planoAtualComercial["LimiteUsuariosExibicao"] = $planoAtual["LimiteUsuarios"] ?? null;

        if (($planoAtual["Slug"] ?? "") === "teste-assistido") {
            $planoAtualComercial["NomeExibicao"] = "Teste Assistido";
            $planoAtualComercial["DescricaoExibicao"] = "Avaliação inicial com implantação assistida";
            $planoAtualComercial["ValorExibicao"] = 0.00;
            $planoAtualComercial["LimiteOSMesExibicao"] = 10;
            $planoAtualComercial["LimiteUsuariosExibicao"] = 1;
        }
    }
}
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page-wide">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Meu plano</h3>

            <p>
                Acompanhe o plano da empresa, limites de uso e recursos disponíveis no DirectOS.
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

    <?php if ($planoAtualComercial): ?>
        <?php
            $limiteOS = $planoAtualComercial["LimiteOSMesExibicao"] ?? null;
            $limiteUsuarios = $planoAtualComercial["LimiteUsuariosExibicao"] ?? null;

            $percentualOS = percentualUsoPlano($totalMes, $limiteOS);
            $percentualUsuarios = percentualUsoPlano($totalUsuarios, $limiteUsuarios);
        ?>

        <div class="row g-3 mb-4">

            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="small text-muted">Plano atual</div>

                        <h4 class="mb-1 mt-2 text-primary">
                            <?= htmlspecialchars($planoAtualComercial["NomeExibicao"]) ?>
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
                            R$ <?= number_format((float)$planoAtualComercial["ValorExibicao"], 2, ",", ".") ?>
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

        <?php if (($planoAtual["Slug"] ?? "") === "teste-assistido"): ?>
            <div class="alert alert-info mb-4">
                <strong>Teste Assistido</strong><br>
                R$ 0 · Até 10 OS/mês · 1 usuário<br>
                Avaliação inicial com implantação assistida
            </div>
        <?php endif; ?>

        <div class="card form-card mb-4">
            <div class="card-header">
                Uso do plano
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

            <p>
                Compare os planos comerciais disponíveis e escolha o mais adequado para a operação da empresa.
            </p>
        </div>
    </div>

    <div class="row g-4">

        <?php foreach ($planos as $plano): ?>
            <?php
                $ehAtual = $planoAtualComercial && (int)$planoAtualComercial["PlanoId"] === (int)$plano["PlanoId"];
                $valorMensal = (float)$plano["ValorExibicao"];
                $classeCard = $plano["Destaque"] ? "border border-primary border-2" : "";
            ?>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100 <?= $classeCard ?>">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h4 class="mb-1">
                                    <?= htmlspecialchars($plano["NomeExibicao"]) ?>
                                </h4>

                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($ehAtual): ?>
                                        <span class="badge bg-primary">
                                            Plano atual
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($plano["Tag"])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($plano["Tag"]) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <p class="text-muted">
                            <?= htmlspecialchars($plano["DescricaoExibicao"] ?? "") ?>
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
                            <?php foreach ($plano["RecursosExibicao"] as $recurso): ?>
                                <li><?= htmlspecialchars($recurso) ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="mt-auto">
                            <?php if ($ehAtual): ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    Plano atual
                                </button>
                            <?php else: ?>
                                <?php if ($usuarioSuperAdmin): ?>
                                <form method="post" action="alterar.php">
                                    <?= csrfInput() ?>

                                    <input type="hidden" name="plano" value="<?= (int)$plano["PlanoId"] ?>">

                                    <button
                                        type="submit"
                                        class="btn <?= $plano["Destaque"] ? "btn-primary" : "btn-outline-primary" ?> w-100"
                                        onclick="return confirm('Deseja alterar para o plano <?= htmlspecialchars($plano["NomeExibicao"]) ?>?')"
                                    >
                                        Alterar para este plano
                                    </button>
                                </form>
                                <?php else: ?>
                                    <a href="https://wa.me/5541999113960?text=Olá,%20gostaria%20de%20alterar%20meu%20plano%20no%20DirectOS."
                                    target="_blank"
                                    class="btn btn-outline-primary w-100">
                                        Solicitar alteração de plano
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
