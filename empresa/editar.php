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
        DataCadastro
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