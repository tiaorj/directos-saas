<?php
require_once "../includes/proteger.php";
require_once "../config/conexao.php";

$empresaId = (int)$_SESSION["EmpresaId"];

$sql = "
    SELECT
        EmpresaId,
        NomeFantasia,
        RazaoSocial,
        Cnpj,
        Email,
        Telefone,
        WhatsApp,
        Slug,
        Ativo,
        DataCadastro,
        OcultarOnboarding
    FROM OS_Empresas
    WHERE EmpresaId = :EmpresaId
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
$stmt->execute();

$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    die("Empresa não encontrada.");
}

function contarRegistrosEmpresa($conn, $sql, $empresaId)
{
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":EmpresaId", $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

$totalServicosAtivos = contarRegistrosEmpresa($conn, "
    SELECT COUNT(*)
    FROM OS_Servicos
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
", $empresaId);

$totalClientesAtivos = contarRegistrosEmpresa($conn, "
    SELECT COUNT(*)
    FROM OS_Clientes
    WHERE EmpresaId = :EmpresaId
      AND Ativo = 1
", $empresaId);

$totalOrdensServico = contarRegistrosEmpresa($conn, "
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
$sucesso = $_GET["sucesso"] ?? "";
$erro = $_GET["erro"] ?? "";
?>

<?php require_once "../includes/header.php"; ?>
<?php require_once "../includes/menu.php"; ?>

<div class="container-fluid form-page">

    <div class="form-header">
        <div>
            <h3 class="mb-1">Minha Empresa</h3>
            <p>
                Atualize os dados da empresa exibidos no sistema, na área do cliente e nos links públicos.
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

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Status da empresa</div>

                    <div class="mt-2">
                        <?php if ((int)$empresa["Ativo"] === 1): ?>
                            <span class="badge bg-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativa</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Slug</div>

                    <h6 class="mb-0 mt-2">
                        <?= htmlspecialchars($empresa["Slug"] ?? "-") ?>
                    </h6>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Data de cadastro</div>

                    <h6 class="mb-0 mt-2">
                        <?= !empty($empresa["DataCadastro"]) 
                            ? date("d/m/Y H:i", strtotime($empresa["DataCadastro"])) 
                            : "-" 
                        ?>
                    </h6>
                </div>
            </div>
        </div>

    </div>

    <?php if ($onboardingConcluido): ?>

        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <div>
                <strong>Checklist inicial concluído.</strong>
                <br>
                Sua empresa já completou os primeiros passos: dados da empresa, serviço, cliente e primeira OS.
            </div>

            <a href="../dashboard.php" class="btn btn-sm btn-success">
                Ir para o Dashboard
            </a>
        </div>

    <?php elseif ($onboardingOculto): ?>

        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>
                <strong>Checklist inicial oculto.</strong>
                <br>
                Você ocultou os primeiros passos do Dashboard. Pode reexibir quando quiser.
            </div>

            <a href="alternar_onboarding.php?acao=exibir&origem=empresa" class="btn btn-sm btn-primary">
                Reexibir checklist
            </a>
        </div>

    <?php else: ?>

        <div class="alert alert-light border d-flex justify-content-between align-items-center">
            <div>
                <strong>Checklist inicial ativo.</strong>
                <br>
                O Dashboard mostrará os primeiros passos enquanto houver pendências.
            </div>

            <a 
                href="alternar_onboarding.php?acao=ocultar&origem=empresa" 
                class="btn btn-sm btn-outline-secondary"
                onclick="return confirm('Deseja ocultar o checklist inicial do Dashboard?')"
            >
                Ocultar checklist
            </a>
        </div>

    <?php endif; ?>

    <div class="card form-card">
        <div class="card-header">
            Dados da Empresa
        </div>

        <div class="card-body">
            <form method="post" action="atualizar.php">

                <input type="hidden" name="EmpresaId" value="<?= (int)$empresa["EmpresaId"] ?>">

                <div class="form-section-title">
                    Identificação
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-label">Nome Fantasia</label>
                        <input 
                            type="text" 
                            name="NomeFantasia" 
                            class="form-control" 
                            required
                            maxlength="150"
                            value="<?= htmlspecialchars($empresa["NomeFantasia"] ?? "") ?>"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Razão Social</label>
                        <input 
                            type="text" 
                            name="RazaoSocial" 
                            class="form-control"
                            maxlength="150"
                            value="<?= htmlspecialchars($empresa["RazaoSocial"] ?? "") ?>"
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CNPJ</label>
                        <input 
                            type="text" 
                            name="Cnpj" 
                            class="form-control"
                            maxlength="20"
                            value="<?= htmlspecialchars($empresa["Cnpj"] ?? "") ?>"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Slug</label>
                        <input 
                            type="text" 
                            name="Slug" 
                            class="form-control"
                            maxlength="80"
                            value="<?= htmlspecialchars($empresa["Slug"] ?? "") ?>"
                        >
                        <div class="input-help">
                            Usado futuramente para URL personalizada da empresa.
                        </div>
                    </div>
                </div>

                <div class="form-section-title">
                    Contato
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input 
                            type="text" 
                            name="Telefone" 
                            class="form-control"
                            maxlength="20"
                            value="<?= htmlspecialchars($empresa["Telefone"] ?? "") ?>"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input 
                            type="text" 
                            name="WhatsApp" 
                            class="form-control"
                            maxlength="20"
                            value="<?= htmlspecialchars($empresa["WhatsApp"] ?? "") ?>"
                        >
                        <div class="input-help">
                            Informe com DDD. Exemplo: 21999999999
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">E-mail</label>
                        <input 
                            type="email" 
                            name="Email" 
                            class="form-control"
                            maxlength="150"
                            value="<?= htmlspecialchars($empresa["Email"] ?? "") ?>"
                        >
                    </div>
                </div>

                <div class="alert alert-info mb-0">
                    <strong>Observação:</strong>
                    os dados de WhatsApp, e-mail e nome fantasia podem aparecer na área pública do cliente e nos links de acompanhamento da OS.
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        Salvar Alterações
                    </button>

                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>